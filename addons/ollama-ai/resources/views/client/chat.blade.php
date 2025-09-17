@extends('templates/wrapper', [
    'css' => ['body' => 'bg-neutral-50 dark:bg-neutral-900'],
])

@section('container')
    <div id="modal-portal"></div>
    
    <div class="w-full mx-auto">
        <div class="flex flex-col lg:flex-row">
            <!-- Navigation sidebar would be here -->
            
            <div class="w-full lg:w-3/4 lg:pl-6">
                <div class="flex flex-col space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                🤖 AI Assistant
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">
                                Chat with AI to get help with your servers and configurations
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-1.5 animate-pulse"></div>
                                Connected
                            </span>
                        </div>
                    </div>

                    <!-- Chat Interface -->
                    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                AI Chat
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <!-- Chat messages area -->
                            <div id="chat-messages" class="h-96 overflow-y-auto border rounded-lg p-4 bg-gray-50 dark:bg-gray-900 space-y-4">
                                <!-- Welcome message -->
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-.464 5.535a1 1 0 10-1.415-1.414 3 3 0 01-4.242 0 1 1 0 00-1.415 1.414 5 5 0 007.072 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                👋 Hello! I'm your AI assistant. I can help you with:
                                            </p>
                                            <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside space-y-1">
                                                <li>Server configuration and optimization</li>
                                                <li>Troubleshooting common issues</li>
                                                <li>Code generation for automation</li>
                                                <li>Performance analysis and recommendations</li>
                                            </ul>
                                            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                                How can I help you today?
                                            </p>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">AI Assistant • now</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chat input -->
                            <div class="flex space-x-3">
                                <div class="flex-1">
                                    <textarea 
                                        id="message-input" 
                                        rows="2" 
                                        placeholder="Type your message here..." 
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 resize-none"
                                    ></textarea>
                                </div>
                                <div class="flex-shrink-0">
                                    <button 
                                        id="send-button"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed h-full"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        Send
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick suggestions -->
                            <div class="flex flex-wrap gap-2">
                                <button class="suggestion-btn px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    How do I optimize my server?
                                </button>
                                <button class="suggestion-btn px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    Generate a startup script
                                </button>
                                <button class="suggestion-btn px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    Analyze server performance
                                </button>
                                <button class="suggestion-btn px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    Help with Docker configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const chatMessages = document.getElementById('chat-messages');
    const suggestionButtons = document.querySelectorAll('.suggestion-btn');
    
    // Handle sending messages
    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage('user', message);
        messageInput.value = '';
        
        // Show AI thinking
        showTyping();
        
        // Simulate AI response (in real implementation, this would call the API)
        setTimeout(() => {
            hideTyping();
            addMessage('ai', 'This is a placeholder response. In the full implementation, I would provide helpful AI-generated responses based on your query.');
        }, 2000);
    }
    
    // Add message to chat
    function addMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex items-start space-x-3';
        
        const isUser = sender === 'user';
        const alignment = isUser ? 'justify-end' : '';
        
        messageDiv.innerHTML = `
            ${!isUser ? `
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-.464 5.535a1 1 0 10-1.415-1.414 3 3 0 01-4.242 0 1 1 0 00-1.415 1.414 5 5 0 007.072 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            ` : ''}
            <div class="flex-1 ${alignment}">
                <div class="bg-${isUser ? 'blue-500' : 'white dark:bg-gray-800'} rounded-lg p-3 shadow-sm ${isUser ? 'text-white ml-12' : ''}">
                    <p class="text-sm ${isUser ? 'text-white' : 'text-gray-700 dark:text-gray-300'}">${text}</p>
                </div>
                <p class="mt-1 text-xs text-gray-500 ${alignment}">
                    ${isUser ? 'You' : 'AI Assistant'} • now
                </p>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Show AI typing indicator
    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typing-indicator';
        typingDiv.className = 'flex items-start space-x-3';
        typingDiv.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-.464 5.535a1 1 0 10-1.415-1.414 3 3 0 01-4.242 0 1 1 0 00-1.415 1.414 5 5 0 007.072 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        <span class="text-sm text-gray-500 ml-2">AI is thinking...</span>
                    </div>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Hide AI typing indicator
    function hideTyping() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // Suggestion buttons
    suggestionButtons.forEach(button => {
        button.addEventListener('click', function() {
            messageInput.value = this.textContent;
            messageInput.focus();
        });
    });
});
</script>

@endsection