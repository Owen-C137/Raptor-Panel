@extends('layouts.app')

@section('title')
    AI Assistant
@endsection

@section('content-header')
    <h1>AI Assistant <small>Your intelligent helper</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">Home</a></li>
        <li class="active">AI Assistant</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        @if(!$ai_enabled)
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>AI Assistant is currently disabled.</strong>
            Please contact an administrator to enable AI features.
        </div>
        @endif

        <div class="box box-primary" id="ai-chat-container">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-comments"></i> AI Chat
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-toggle="tooltip" title="New Conversation" onclick="startNewConversation()">
                        <i class="fa fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-toggle="tooltip" title="Settings">
                        <i class="fa fa-gear"></i>
                    </button>
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            
            <div class="box-body" style="height: 500px; display: flex; flex-direction: column;">
                <!-- Chat Messages Area -->
                <div class="chat-messages" id="chat-messages" style="flex: 1; overflow-y: auto; padding: 10px; border: 1px solid #f0f0f0; margin-bottom: 10px;">
                    <div class="welcome-message text-center text-muted" id="welcome-message">
                        <i class="fa fa-robot fa-3x" style="margin-bottom: 10px;"></i>
                        <h4>Welcome to your AI Assistant!</h4>
                        <p>Ask me anything about your servers, configurations, or get help with troubleshooting.</p>
                        
                        <div class="quick-suggestions" id="quick-suggestions">
                            <h5>Quick suggestions:</h5>
                            <div class="btn-group-vertical" role="group">
                                <button type="button" class="btn btn-default btn-sm suggestion-btn" data-message="How can I improve my server performance?">
                                    <i class="fa fa-tachometer"></i> How can I improve my server performance?
                                </button>
                                <button type="button" class="btn btn-default btn-sm suggestion-btn" data-message="Help me troubleshoot connection issues">
                                    <i class="fa fa-exclamation-circle"></i> Help me troubleshoot connection issues
                                </button>
                                <button type="button" class="btn btn-default btn-sm suggestion-btn" data-message="What are the best practices for server management?">
                                    <i class="fa fa-cogs"></i> What are the best practices for server management?
                                </button>
                                <button type="button" class="btn btn-default btn-sm suggestion-btn" data-message="Explain different server configurations">
                                    <i class="fa fa-question-circle"></i> Explain different server configurations
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Input Area -->
                <div class="chat-input-area">
                    <form id="chat-form" style="display: flex; gap: 10px;">
                        <input type="hidden" id="conversation-id" value="">
                        <input type="hidden" id="context-type" value="general">
                        <input type="hidden" id="context-id" value="">
                        
                        <div style="flex: 1;">
                            <input type="text" 
                                   id="message-input" 
                                   class="form-control" 
                                   placeholder="Type your message..." 
                                   maxlength="2000"
                                   {{ !$ai_enabled ? 'disabled' : '' }}>
                        </div>
                        <button type="submit" 
                                class="btn btn-primary" 
                                id="send-button"
                                {{ !$ai_enabled ? 'disabled' : '' }}>
                            <i class="fa fa-paper-plane"></i>
                        </button>
                    </form>
                    
                    <div class="chat-status mt-2" style="font-size: 12px; color: #999;">
                        <span id="typing-indicator" style="display: none;">
                            <i class="fa fa-circle-o-notch fa-spin"></i> AI is thinking...
                        </span>
                        <span id="status-text">Ready to chat</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversation History Sidebar -->
        <div class="box box-default" id="conversation-history">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-history"></i> Recent Conversations
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" onclick="refreshConversations()">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="box-body" style="max-height: 300px; overflow-y: auto;">
                <div id="conversations-list">
                    @if(count($conversations) > 0)
                        @foreach($conversations as $conversation)
                        <div class="conversation-item" data-id="{{ $conversation['id'] }}">
                            <div class="conversation-header">
                                <strong>{{ $conversation['title'] }}</strong>
                                <span class="label label-default pull-right">{{ $conversation['context_type'] }}</span>
                            </div>
                            <div class="conversation-preview text-muted">
                                {{ $conversation['last_message'] ?: 'No messages yet' }}
                            </div>
                            <div class="conversation-meta">
                                <small class="text-muted">{{ $conversation['updated_at'] }}</small>
                                <button type="button" class="btn btn-xs btn-danger pull-right" onclick="deleteConversation({{ $conversation['id'] }})">
                                    <i class="fa fa-trash"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-primary pull-right" style="margin-right: 5px;" onclick="loadConversation({{ $conversation['id'] }})">
                                    <i class="fa fa-comments"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No conversations yet. Start chatting to create your first conversation!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Status Widget (Floating) -->
<div id="ai-status-widget" class="ai-floating-widget">
    <div class="widget-header">
        <i class="fa fa-robot"></i>
        <span class="status-text">AI Ready</span>
        <button type="button" class="btn btn-xs btn-link" onclick="toggleAiWidget()">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <div class="widget-body" id="ai-widget-body" style="display: none;">
        <div class="quick-chat">
            <input type="text" id="quick-message" class="form-control input-sm" placeholder="Quick question...">
            <button type="button" class="btn btn-primary btn-sm" onclick="sendQuickMessage()">Send</button>
        </div>
        <div class="quick-response" id="quick-response" style="display: none;">
            <div class="response-text"></div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .chat-messages {
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            max-width: 80%;
        }
        
        .message.user {
            background: #007bff;
            color: white;
            margin-left: auto;
            text-align: right;
        }
        
        .message.ai {
            background: white;
            border: 1px solid #ddd;
            margin-right: auto;
        }
        
        .message .message-content {
            margin-bottom: 5px;
        }
        
        .message .message-meta {
            font-size: 11px;
            opacity: 0.7;
        }
        
        .conversation-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        
        .conversation-item:hover {
            background: #f5f5f5;
        }
        
        .conversation-header {
            margin-bottom: 5px;
        }
        
        .conversation-preview {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .conversation-meta {
            font-size: 11px;
            border-top: 1px solid #f0f0f0;
            padding-top: 5px;
        }
        
        .suggestion-btn {
            margin: 2px 0;
            text-align: left;
            white-space: normal;
            max-width: 300px;
        }
        
        .ai-floating-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .widget-header {
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .widget-body {
            padding: 10px;
        }
        
        .quick-chat {
            display: flex;
            gap: 5px;
        }
        
        .quick-response {
            margin-top: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .ai-floating-widget {
                width: calc(100vw - 40px);
                right: 20px;
                left: 20px;
            }
        }
    </style>

    <script>
        let currentConversationId = null;
        let aiEnabled = {{ $ai_enabled ? 'true' : 'false' }};

        $(document).ready(function() {
            initializeChat();
        });

        function initializeChat() {
            // Handle chat form submission
            $('#chat-form').on('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });

            // Handle suggestion clicks
            $('.suggestion-btn').on('click', function() {
                const message = $(this).data('message');
                $('#message-input').val(message);
                sendMessage();
            });

            // Auto-resize textarea and enable enter key
            $('#message-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Load AI status
            checkAiStatus();
        }

        function sendMessage() {
            if (!aiEnabled) {
                toastr.error('AI Assistant is currently disabled');
                return;
            }

            const message = $('#message-input').val().trim();
            if (!message) return;

            const conversationId = $('#conversation-id').val();
            const contextType = $('#context-type').val();
            const contextId = $('#context-id').val();

            // Show user message immediately
            appendMessage('user', message);
            $('#message-input').val('');
            showTypingIndicator();

            // Send to backend
            $.ajax({
                url: '{{ route("client.ai.send") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    message: message,
                    conversation_id: conversationId,
                    context_type: contextType,
                    context_id: contextId
                },
                success: function(response) {
                    if (response.success) {
                        appendMessage('ai', response.ai_response, response.processing_time);
                        currentConversationId = response.conversation_id;
                        $('#conversation-id').val(response.conversation_id);
                        hideWelcomeMessage();
                        refreshConversations();
                    } else {
                        appendMessage('ai', 'Sorry, I encountered an error: ' + response.message);
                    }
                },
                error: function() {
                    appendMessage('ai', 'Sorry, I\'m having trouble connecting. Please try again.');
                },
                complete: function() {
                    hideTypingIndicator();
                }
            });
        }

        function appendMessage(role, content, processingTime = null) {
            const messagesContainer = $('#chat-messages');
            const messageClass = role === 'user' ? 'message user' : 'message ai';
            const timestamp = new Date().toLocaleTimeString();
            
            const processingInfo = processingTime ? ` (${processingTime}ms)` : '';
            
            const messageHtml = `
                <div class="${messageClass}">
                    <div class="message-content">${content}</div>
                    <div class="message-meta">${timestamp}${processingInfo}</div>
                </div>
            `;
            
            messagesContainer.append(messageHtml);
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        }

        function showTypingIndicator() {
            $('#typing-indicator').show();
            $('#status-text').hide();
        }

        function hideTypingIndicator() {
            $('#typing-indicator').hide();
            $('#status-text').show().text('Ready to chat');
        }

        function hideWelcomeMessage() {
            $('#welcome-message').fadeOut();
        }

        function startNewConversation() {
            currentConversationId = null;
            $('#conversation-id').val('');
            $('#chat-messages').empty();
            $('#welcome-message').show();
            $('#status-text').text('New conversation started');
        }

        function loadConversation(conversationId) {
            $.ajax({
                url: `/client/ai/conversations/${conversationId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        currentConversationId = conversationId;
                        $('#conversation-id').val(conversationId);
                        $('#chat-messages').empty();
                        
                        response.messages.forEach(function(message) {
                            appendMessage(message.role, message.content, message.processing_time);
                        });
                        
                        hideWelcomeMessage();
                        $('#status-text').text(`Loaded: ${response.conversation.title}`);
                    }
                },
                error: function() {
                    toastr.error('Failed to load conversation');
                }
            });
        }

        function deleteConversation(conversationId) {
            if (!confirm('Are you sure you want to delete this conversation?')) {
                return;
            }

            $.ajax({
                url: `/client/ai/conversations/${conversationId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Conversation deleted');
                        refreshConversations();
                        
                        if (currentConversationId === conversationId) {
                            startNewConversation();
                        }
                    }
                },
                error: function() {
                    toastr.error('Failed to delete conversation');
                }
            });
        }

        function refreshConversations() {
            // Simple reload for now - in production you might want to fetch via AJAX
            location.reload();
        }

        function checkAiStatus() {
            $.ajax({
                url: '{{ route("client.ai.status") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateAiStatus(response.status);
                    }
                },
                error: function() {
                    updateAiStatus({ enabled: false, available: false });
                }
            });
        }

        function updateAiStatus(status) {
            const statusWidget = $('#ai-status-widget .status-text');
            
            if (status.enabled && status.available) {
                statusWidget.text('AI Ready');
                statusWidget.removeClass('text-danger text-warning').addClass('text-success');
            } else if (status.enabled && !status.available) {
                statusWidget.text('AI Unavailable');
                statusWidget.removeClass('text-success text-danger').addClass('text-warning');
            } else {
                statusWidget.text('AI Disabled');
                statusWidget.removeClass('text-success text-warning').addClass('text-danger');
            }
        }

        function toggleAiWidget() {
            $('#ai-widget-body').toggle();
        }

        function sendQuickMessage() {
            const message = $('#quick-message').val().trim();
            if (!message || !aiEnabled) return;

            $('#quick-response').show().find('.response-text').html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            
            $.ajax({
                url: '{{ route("client.ai.send") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    message: message,
                    context_type: 'general'
                },
                success: function(response) {
                    if (response.success) {
                        $('#quick-response .response-text').text(response.ai_response);
                        $('#quick-message').val('');
                    } else {
                        $('#quick-response .response-text').text('Error: ' + response.message);
                    }
                },
                error: function() {
                    $('#quick-response .response-text').text('Sorry, I\'m having trouble connecting.');
                }
            });
        }
    </script>
@endsection