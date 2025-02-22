<?php

namespace App\Models;

use App\Services\OpenAIGateway;
use App\Traits\UsesChat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory, UsesChat;

    protected $guarded = [];

    public function getRetrievalTask()
    {
        // First see if we have a task named "Basic LLM Retrieval"
        $task = $this->tasks()->where('name', 'LLM Chat With Knowledge Retrieval')->first();

        // If not, create it
        if (! $task) {
            $task = $this->createRetrievalTask();
        }

        return $task;
    }

    public function getChatTask()
    {
        // First see if we have a task named "Basic LLM Chat"
        $task = $this->tasks()->where('name', 'Basic LLM Chat')->first();

        // If not, create it
        if (! $task) {
            $task = $this->createChatTask();
        }

        return $task;
    }

    public function createChatTask()
    {
        $task = Task::create([
            'name' => 'Basic LLM Chat',
            'description' => 'Send input to LLM and return response',
            'agent_id' => $this->id,
        ]);

        $task->steps()->create([
            'name' => 'Default Step',
            'order' => 1,
            'task_id' => $task->id,
            'agent_id' => $this->id,
            'entry_type' => 'input',
            'category' => 'validation',
            'error_message' => 'Sorry, I didn\'t understand that.',
            'success_action' => 'next_node',
        ]);

        $task->steps()->create([
            'name' => 'Default Step',
            'order' => 2,
            'task_id' => $task->id,
            'agent_id' => $this->id,
            'entry_type' => 'node',
            'category' => 'inference',
            'error_message' => 'Sorry, inference failed',
            'success_action' => 'json_response',
        ]);

        return $task;
    }

    public function createRetrievalTask()
    {
        $task = Task::create([
            'name' => 'LLM Chat With Knowledge Retrieval',
            'description' => 'Chat with LLM using knowledge retrieval.',
            'agent_id' => $this->id,
        ]);

        // Create the steps
        $step1 = Step::create([
            'agent_id' => $this->id,
            'category' => 'validation',
            'description' => 'Ensure input is a valid chat message',
            'entry_type' => 'input',
            'error_message' => 'Could not validate input',
            'name' => 'Validate Input',
            'order' => 1,
            'success_action' => 'next_node',
            'task_id' => $task->id,
        ]);

        $step2 = Step::create([
            'agent_id' => $this->id,
            'category' => 'embedding',
            'description' => 'Convert input to vector embedding',
            'entry_type' => 'node',
            'error_message' => 'Could not generate embedding',
            'name' => 'Embed Input',
            'order' => 2,
            'success_action' => 'next_node',
            'task_id' => $task->id,
        ]);

        $step3 = Step::create([
            'agent_id' => $this->id,
            'category' => 'similarity_search',
            'description' => 'Compare input to knowledge base',
            'entry_type' => 'node',
            'error_message' => 'Could not run similarity search',
            'name' => 'Similarity Search',
            'order' => 3,
            'success_action' => 'next_node',
            'task_id' => $task->id,
        ]);

        $step4 = Step::create([
            'agent_id' => $this->id,
            'category' => 'inference',
            'description' => 'Call to LLM to generate response',
            'entry_type' => 'node',
            'error_message' => 'Could not call to LLM',
            'name' => 'Call LLM',
            'order' => 4,
            'success_action' => 'json_response',
            'task_id' => $task->id,
        ]);

        return $task;
    }

    public function getUserConversation()
    {
        // if user_id is null, return a new conversation
        if (! auth()->id()) {
            $convo = Conversation::create([
                'user_id' => null,
                'agent_id' => $this->id,
            ]);
        } else {
            $convo = $this->conversations()->where('user_id', auth()->id())->first();

            if (! $convo) {
                $convo = Conversation::create([
                    'user_id' => auth()->id(),
                    'agent_id' => $this->id,
                ]);
            }
        }

        $convo->load('messages');

        return $convo;
    }

    /**
     * Run a specific task on the agent.
     *
     * @return mixed
     */
    public function runTask(Task $task, $input, callable $logFunction = null, callable $streamFunction = null)
    {
        // Call the existing run method with the input data
        return $this->run($input, $task, $logFunction, $streamFunction);
    }

    public function run($input, $task = null, $logFunction = null, $streamFunction = null)
    {
        $conversation = $this->getUserConversation();

        // Append the input to the conversation
        $conversation->messages()->create([
            'user_id' => auth()->id(),
            'body' => $input['input'],
            'sender' => 'user',
        ]);

        $userInput = $input;
        if (! $task) {
            // If no provided task, get the first task
            $task = $this->tasks()->first()->load('steps');
        }

        // Create from it a TaskExecuted
        $task_executed = TaskExecuted::create([
            'task_id' => $task->id,
            // Current user ID if authed or null
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        foreach ($task->steps as $step) {
            // Log the step name using the provided logging function
            if ($logFunction) {
                $logFunction($step->name);
            }
            if ($step->order !== 1) {
                $input = $prev_step_executed->output;
            }

            if ($step->name === 'LLM Inference') {
                $input = json_encode([
                    'input' => "Respond to this user input with the following context: \n User Input: {$userInput['input']} \n\n Context: {$input}",
                ]);
            }

            // if step category is "plugin", augment the input with params
            if ($step->category === 'plugin') {
                $params = json_decode($step->params);

                // If the previous step executed is the first step, use the input directly
                // Otherwise, use the output of the previous step executed
                if ($step->order === 1) {
                    $input = json_encode([
                        'input' => $input,
                        'plugin_id' => $params->plugin_id,
                        'function' => $params->function,
                    ]);
                } elseif ($step->order === 2) {

                    // the previous step output will either be an array with a key of output or just a string, so handle both cases
                    if (is_array($prev_step_executed->output)) {
                        $input = json_encode([
                            'input' => $prev_step_executed->output['output'], // Assign the output directly
                            'plugin_id' => $params->plugin_id,
                            'function' => $params->function,
                        ]);
                    } else {
                        // temporarily take this array of strings - and json decode and grab the first element and pass that to the input array as ['url' => {first element}]
                        $temp = json_decode($prev_step_executed->output);
                        // dd($temp); // "["https://raw.githubusercontent.com/OpenAgentsInc/plugin-url-scraper/main/src/lib.rs"]"
                        $temp = json_decode($temp);

                        $input = json_encode([
                            'input' => [
                                'url' => $temp[0], // Assign the output directly
                            ],
                            'plugin_id' => $params->plugin_id,
                            'function' => $params->function,
                        ]);
                    }
                }
            } elseif ($step->category === 'L402') {
                $params = json_decode($step->params);
                $input = json_encode([
                    // 'input' => $prev_step_executed->output, // Assign the output directly
                    'url' => $params->url,
                ]);
            } else {
                $input = json_encode($input);
            }

            // Create a new StepExecuted with this step and task_executed
            $step_executed = StepExecuted::create([
                'step_id' => $step->id,
                'input' => $input,
                'order' => $step->order,
                'task_executed_id' => $task_executed->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
            ]);
            $step_executed->output = $step_executed->run($conversation, $streamFunction);
            $step_executed->save();

            $prev_step_executed = $step_executed;
        }

        $lastoutput = $step_executed->fresh()->output;

        // We get output as json - convert to array
        $output = json_decode($lastoutput, true);

        // Append the input to the conversation
        $conversation->messages()->create([
            'user_id' => auth()->id(),
            'body' => $output['output'],
            'sender' => 'agent',
        ]);

        // Return the output of the final StepExecuted
        return $step_executed->fresh()->output;
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function steps()
    {
        return $this->hasMany(Step::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function thoughts()
    {
        return $this->hasMany(Thought::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sendMessage($conversationId, $body)
    {
        Message::create([
            'sender' => 'agent',
            'conversation_id' => $conversationId,
            'user_id' => $this->user->id,
            'body' => $body,
        ]);
    }

    public function reflect()
    {
        try {
            // Grab all the steps for this agent
            $steps = $this->steps()->get();

            $thoughts = [];

            // Loop through each step and create a Thought for each
            foreach ($steps as $step) {
                $messages = [
                    ['role' => 'system', 'content' => 'You are an AI agent specializing in understanding unstructured data.'],
                    ['role' => 'user', 'content' => 'What do you notice about this data?: '.json_encode($step)],
                ];
                $input = [
                    'model' => 'gpt-4',
                    'messages' => $messages,
                ];

                $gateway = new OpenAIGateway();
                $response = $gateway->makeChatCompletion($input);

                $output = $response['choices'][0];
                $comment = $output['message']['content'];

                $thought = Thought::create([
                    'agent_id' => $this->id,
                    'body' => $comment,
                    // 'body' => "I notice that I have {$steps->count()} steps."
                ]);

                $thoughts[] = $thought->body;
            }

            return $thoughts;
        } catch (\Exception $e) {
            // If $thoughts count is greater than 0, return the thoughts - otherwise return the error message
            if (count($thoughts) > 0) {
                return $thoughts;
            }

            return $e->getMessage();
        }
    }
}
