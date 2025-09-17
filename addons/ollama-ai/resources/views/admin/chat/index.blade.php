@extends('layouts.admin')

@section('title')
    AI Chat Interface
@endsection

@push('head-scripts')
@endpush

@section('content-header')
    <h1>AI Chat Interface <small>Direct chat with AI models</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li class="active">Chat</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Chat Interface -->
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-comments"></i> AI Chat
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-info btn-sm" id="new-conversation">
                        <i class="fa fa-plus"></i> New Chat
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-brain"></i> <span id="current-model">{{ env('AI_DEFAULT_MODEL', 'llama3.2') }}</span>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu pull-right" id="model-dropdown">
                            <li><a href="#" data-loading="true"><i class="fa fa-spinner fa-spin"></i> Loading models...</a></li>
                        </ul>
                    </div>
                    <div class="connection-status pull-right" style="margin-right: 10px;">
                        <span id="connection-indicator" class="label label-default">
                            <i class="fa fa-circle-o-notch fa-spin"></i> Checking...
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="box-body" style="padding: 0;">
                <!-- Chat Messages Area -->
                <div class="chat-messages" id="chat-messages">
                    <div class="welcome-message text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-robot fa-3x" style="margin-bottom: 20px; color: #ccc;"></i>
                        <h4>Welcome to AI Admin Chat</h4>
                        <p>Start a conversation to get help with Pterodactyl Panel management, server analysis, or any technical questions.</p>
                        <div class="quick-prompts" style="margin-top: 20px;">
                            <button class="btn btn-default btn-sm prompt-btn" data-prompt="Analyze server performance across my infrastructure">
                                <i class="fa fa-tachometer"></i> Analyze Performance
                            </button>
                            <button class="btn btn-default btn-sm prompt-btn" data-prompt="Help me troubleshoot a server issue">
                                <i class="fa fa-wrench"></i> Troubleshoot
                            </button>
                            <button class="btn btn-default btn-sm prompt-btn" data-prompt="Generate a security report for my servers">
                                <i class="fa fa-shield"></i> Security Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Input Area -->
                <div class="chat-input-area" style="border-top: 1px solid #f0f0f0; padding: 15px;">
                    <form id="chat-form">
                        @csrf
                        <input type="hidden" id="conversation-id" name="conversation_id" value="">
                        <input type="hidden" id="selected-model" name="model" value="{{ env('AI_DEFAULT_MODEL', 'llama3.2') }}">
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <input type="text" 
                                           id="message-input" 
                                           name="message" 
                                           class="form-control" 
                                           placeholder="Type your message..."
                                           maxlength="2000"
                                           autocomplete="off">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-primary" id="send-button">
                                            <i class="fa fa-paper-plane"></i> Send
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-12">
                                <div class="form-group" style="margin: 0;">
                                    <div class="checkbox" style="margin: 0;">
                                        <label style="font-weight: normal;">
                                            <input type="checkbox" id="enable-system-prompt"> Use custom system prompt
                                        </label>
                                    </div>
                                    <textarea id="system-prompt" 
                                              name="system_prompt" 
                                              class="form-control" 
                                              rows="2" 
                                              placeholder="Enter a custom system prompt for specialized assistance..."
                                              style="display: none; margin-top: 10px;"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <div class="chat-status" style="font-size: 12px; color: #999; margin-top: 10px;">
                        <span id="typing-indicator" style="display: none;">
                            <i class="fa fa-circle-o-notch fa-spin"></i> AI is typing...
                        </span>
                        <span id="status-text">Ready to chat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Recent Conversations -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-history"></i> Recent Conversations
                </h3>
            </div>
            <div class="box-body">
                <div id="recent-conversations">
                    @if(count($recentConversations) > 0)
                        @foreach($recentConversations as $conversation)
                        <div class="conversation-item" data-id="{{ $conversation->id }}">
                            <div class="conversation-title">{{ $conversation->title }}</div>
                            <div class="conversation-meta">
                                <small class="text-muted">
                                    {{ $conversation->updated_at->diffForHumans() }} • 
                                    Model: {{ $conversation->model_used ?? 'Unknown' }}
                                </small>
                            </div>
                            <div class="conversation-actions">
                                <button class="btn btn-xs btn-primary load-conversation" data-id="{{ $conversation->id }}">
                                    <i class="fa fa-folder-open"></i> Load
                                </button>
                                <button class="btn btn-xs btn-danger delete-conversation" data-id="{{ $conversation->id }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No recent conversations yet.</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Available Models -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-brain"></i> Available Models
                </h3>
            </div>
            <div class="box-body">
                <div id="available-models-list">
                    <div class="text-center text-muted">
                        <i class="fa fa-spinner fa-spin"></i> Loading models...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-messages {
    min-height: 400px;
    max-height: 600px;
    overflow-y: auto;
    padding: 15px;
    background: #fafafa;
}

.message {
    margin-bottom: 15px;
    clear: both;
}

.message.user {
    text-align: right;
}

.message.assistant {
    text-align: left;
}

.message-bubble {
    display: inline-block;
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 15px;
    word-wrap: break-word;
}

.message.user .message-bubble {
    background: #3c8dbc;
    color: white;
    border-bottom-right-radius: 5px;
}

.message.assistant .message-bubble {
    background: white;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 5px;
}

.message-meta {
    font-size: 11px;
    color: #999;
    margin: 5px 10px;
}

.conversation-item {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
}

.conversation-item:hover {
    background-color: #f9f9f9;
}

.conversation-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.conversation-meta {
    margin-bottom: 5px;
}

.conversation-actions {
    text-align: right;
}

.prompt-btn {
    margin: 5px;
}

.connection-status {
    line-height: 30px;
}

.model-item {
    padding: 5px;
    border-bottom: 1px solid #f0f0f0;
    font-family: monospace;
}

.model-item:last-child {
    border-bottom: none;
}

.model-item.active {
    background-color: #e3f2fd;
    font-weight: bold;
}
</style>

@section('footer-scripts')
    @parent
    <script>
        console.log('Admin chat script loading...');
        
        // Global variables
        let currentConversationId = null;
        let currentModel = '{{ env("AI_DEFAULT_MODEL", "llama3.2") }}';
        
        // Wait for document ready and jQuery
        $(document).ready(function() {
            console.log('Document ready, jQuery version:', $.fn.jquery || 'undefined');
            
            // Set up CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            
            // Initialize everything
            initializeChat();
        });
        
        function initializeChat() {
            console.log('Initializing chat...');
            
            // Get current model from form
            const selectedModel = $('#selected-model').val();
            if (selectedModel) {
                currentModel = selectedModel;
                $('#current-model').text(currentModel);
            }
            
            // Handle chat form submission - PREVENT DEFAULT
            $('#chat-form').off('submit').on('submit', function(e) {
                console.log('Form submit event triggered');
                e.preventDefault();
                e.stopPropagation();
                sendMessage();
                return false;
            });
            
            // Handle send button click
            $('#send-button').off('click').on('click', function(e) {
                console.log('Send button clicked');
                e.preventDefault();
                e.stopPropagation();
                sendMessage();
                return false;
            });
            
            // Handle Enter key in message input
            $('#message-input').off('keypress').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    console.log('Enter key pressed');
                    e.preventDefault();
                    sendMessage();
                    return false;
                }
            });
            
            // Handle new conversation button
            $('#new-conversation').off('click').on('click', function(e) {
                e.preventDefault();
                startNewConversation();
            });
            
            // Handle system prompt toggle
            $('#enable-system-prompt').off('change').on('change', function() {
                $('#system-prompt').toggle(this.checked);
            });
            
            // Handle quick prompt buttons
            $('.prompt-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const prompt = $(this).data('prompt');
                $('#message-input').val(prompt);
                $('#message-input').focus();
            });
            
            // Handle conversation loading
            $(document).off('click', '.load-conversation').on('click', '.load-conversation', function(e) {
                e.preventDefault();
                const conversationId = $(this).data('id');
                loadConversation(conversationId);
            });
            
            // Handle conversation deletion
            $(document).off('click', '.delete-conversation').on('click', '.delete-conversation', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const conversationId = $(this).data('id');
                if (confirm('Are you sure you want to delete this conversation?')) {
                    deleteConversation(conversationId);
                }
            });
            
            // Handle model selection
            $(document).off('click', '[data-model]').on('click', '[data-model]', function(e) {
                e.preventDefault();
                const model = $(this).data('model');
                selectModel(model);
            });
            
            // Load initial data
            loadAvailableModels();
            checkConnectionStatus();
            
            console.log('Chat initialization complete');
        }
        
        function sendMessage() {
            console.log('sendMessage() called');
            
            const message = $('#message-input').val().trim();
            if (!message) {
                console.log('No message to send');
                return false;
            }
            
            console.log('Sending message:', message);
            
            const data = {
                message: message,
                model: currentModel,
                conversation_id: currentConversationId,
                system_prompt: $('#enable-system-prompt').is(':checked') ? $('#system-prompt').val() : null,
                _token: $('meta[name="_token"]').attr('content')
            };
            
            console.log('CSRF token:', $('meta[name="_token"]').attr('content'));
            console.log('Send data:', data);
            
            // Add user message to chat immediately
            addMessageToChat('user', message);
            
            // Clear input and show typing indicator
            $('#message-input').val('');
            $('#typing-indicator').show();
            $('#status-text').hide();
            $('#send-button').prop('disabled', true);
            
            $.ajax({
                url: '{{ route("admin.ai.chat.send") }}',
                method: 'POST',
                data: data,
                dataType: 'json',
                timeout: 60000, // 60 second timeout
                success: function(response) {
                    console.log('Send response:', response);
                    if (response.success) {
                        // Set conversation ID if this was the first message
                        if (!currentConversationId) {
                            currentConversationId = response.conversation_id;
                            $('#conversation-id').val(currentConversationId);
                            console.log('Set conversation ID:', currentConversationId);
                        }
                        
                        // Add AI response to chat
                        addMessageToChat('assistant', response.message.content);
                        
                        showNotification('success', 'Message sent successfully');
                    } else {
                        console.error('Send failed:', response.error);
                        showNotification('error', 'Error: ' + (response.error || 'Failed to send message'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error, xhr);
                    let errorMsg = 'Failed to send message';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg = response.error;
                        }
                    } catch (e) {
                        if (xhr.status === 419) {
                            errorMsg = 'Session expired. Please refresh the page.';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Server error. Please try again.';
                        } else if (xhr.status === 0) {
                            errorMsg = 'Network error. Please check your connection.';
                        }
                    }
                    showNotification('error', errorMsg);
                },
                complete: function() {
                    $('#typing-indicator').hide();
                    $('#status-text').show().text('Ready to chat');
                    $('#send-button').prop('disabled', false);
                    $('#message-input').focus();
                }
            });
            
            return false;
        }
        
        function loadAvailableModels() {
            console.log('Loading available models...');
            
            $.ajax({
                url: '{{ route("admin.ai.chat.models") }}',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('Models response:', response);
                    
                    if (response.success && response.models) {
                        const models = response.models;
                        let modelDropdown = '';
                        let modelList = '';
                        
                        // Ensure models is an array
                        if (!Array.isArray(models) || models.length === 0) {
                            modelDropdown = '<li><a href="#"><i class="fa fa-exclamation-triangle text-warning"></i> No models available</a></li>';
                            modelList = '<div class="text-muted">No models available</div>';
                        } else {
                            models.forEach(function(model) {
                                const isActive = model.name === currentModel ? 'active' : '';
                                modelDropdown += `<li><a href="#" data-model="${model.name}"><i class="fa fa-brain"></i> ${model.name}</a></li>`;
                                modelList += `<div class="model-item ${isActive}" data-model="${model.name}">${model.name}</div>`;
                            });
                        }
                        
                        $('#model-dropdown').html(modelDropdown);
                        $('#available-models-list').html(modelList);
                        
                        console.log('Models loaded successfully');
                    } else {
                        console.error('Invalid models response:', response);
                        $('#model-dropdown').html('<li><a href="#"><i class="fa fa-exclamation-triangle text-danger"></i> Error loading models</a></li>');
                        $('#available-models-list').html('<div class="text-danger">Error: ' + (response.error || 'Invalid response') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Models loading error:', status, error, xhr);
                    $('#model-dropdown').html('<li><a href="#"><i class="fa fa-exclamation-triangle text-danger"></i> Failed to load models</a></li>');
                    $('#available-models-list').html('<div class="text-danger">Failed to load models</div>');
                }
            });
        }
        
        function checkConnectionStatus() {
            console.log('Checking connection status...');
            
            $.ajax({
                url: '{{ route("admin.ai.chat.status") }}',
                method: 'GET',
                dataType: 'json',
                timeout: 5000,
                success: function(response) {
                    console.log('Status response:', response);
                    if (response.success && response.status && response.status.connected) {
                        $('#connection-indicator')
                            .removeClass('label-default label-danger')
                            .addClass('label-success')
                            .html('<i class="fa fa-check-circle"></i> Connected');
                    } else {
                        $('#connection-indicator')
                            .removeClass('label-default label-success')
                            .addClass('label-danger')
                            .html('<i class="fa fa-times-circle"></i> Disconnected');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Status check error:', status, error);
                    $('#connection-indicator')
                        .removeClass('label-default label-success')
                        .addClass('label-danger')
                        .html('<i class="fa fa-times-circle"></i> Error');
                }
            });
        }
        
        function addMessageToChat(role, content) {
            // Hide welcome message if it exists
            $('.welcome-message').hide();
            
            const messageHtml = `
                <div class="message ${role}">
                    <div class="message-bubble">
                        ${content.replace(/\n/g, '<br>')}
                    </div>
                    <div class="message-meta">
                        ${new Date().toLocaleTimeString()}
                    </div>
                </div>
            `;
            
            $('#chat-messages').append(messageHtml);
            
            // Scroll to bottom
            const chatContainer = $('#chat-messages');
            chatContainer.scrollTop(chatContainer[0].scrollHeight);
        }
        
        function startNewConversation() {
            if (confirm('Start a new conversation? Current chat will be saved.')) {
                currentConversationId = null;
                $('#conversation-id').val('');
                $('#chat-messages').html(`
                    <div class="welcome-message text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-robot fa-3x" style="margin-bottom: 20px; color: #ccc;"></i>
                        <h4>New Admin Chat Session</h4>
                        <p>Start a conversation to get help with Pterodactyl Panel management.</p>
                    </div>
                `);
                $('#message-input').focus();
            }
        }
        
        function selectModel(model) {
            currentModel = model;
            $('#selected-model').val(model);
            $('#current-model').text(model);
            
            // Update active model in sidebar
            $('.model-item').removeClass('active');
            $(`.model-item[data-model="${model}"]`).addClass('active');
            
            showNotification('info', `Switched to model: ${model}`);
            console.log('Model selected:', model);
        }
        
        function showNotification(type, message) {
            console.log('Notification:', type, message);
            
            // Use Pterodactyl's notification system if available
            if (typeof $.notify !== 'undefined') {
                $.notify({
                    message: message
                }, {
                    type: type === 'error' ? 'danger' : type
                });
            } else {
                // Fallback to console and alert for debugging
                console.log('Notification (fallback):', type, message);
                if (type === 'error') {
                    alert('Error: ' + message);
                }
            }
        }
        
        function loadConversation(conversationId) {
            $.ajax({
                url: `/admin/ai/chat/conversation/${conversationId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        currentConversationId = conversationId;
                        $('#conversation-id').val(conversationId);
                        
                        // Clear and populate chat
                        $('#chat-messages').empty();
                        
                        if (response.conversation.messages.length === 0) {
                            $('#chat-messages').html('<div class="text-center text-muted" style="padding: 40px;">No messages in this conversation yet.</div>');
                        } else {
                            response.conversation.messages.forEach(function(message) {
                                addMessageToChat(message.role, message.content);
                            });
                        }
                        
                        // Update model if different
                        if (response.conversation.model_used && response.conversation.model_used !== currentModel) {
                            selectModel(response.conversation.model_used);
                        }
                    } else {
                        showNotification('error', 'Error loading conversation: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function() {
                    showNotification('error', 'Failed to load conversation');
                }
            });
        }
        
        function deleteConversation(conversationId) {
            $.ajax({
                url: `/admin/ai/chat/conversation/${conversationId}`,
                method: 'DELETE',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove from sidebar
                        $(`.conversation-item[data-id="${conversationId}"]`).remove();
                        
                        // If this was the current conversation, clear it
                        if (currentConversationId == conversationId) {
                            startNewConversation();
                        }
                        
                        showNotification('success', 'Conversation deleted successfully');
                    } else {
                        showNotification('error', 'Error: ' + (response.error || 'Failed to delete conversation'));
                    }
                },
                error: function() {
                    showNotification('error', 'Failed to delete conversation');
                }
            });
        }
        
        // Periodically check connection status
        setInterval(checkConnectionStatus, 30000);
        
        // Debug info
        console.log('Admin chat script loaded successfully');
    </script>
@endsection
@endsection