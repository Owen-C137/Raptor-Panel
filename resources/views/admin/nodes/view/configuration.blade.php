@extends('layouts.admin')

@section('title')
    {{ $node->name }}: Configuration
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $node->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Your daemon configuration file.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes') }}">Nodes</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Configuration</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.nodes.view._navigation')

<div class="row">
    <div class="col-sm-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-file-code-o me-2"></i>Configuration File
                </h3>
            </div>
            <div class="block-content p-0">
                <div class="position-relative">
                    <pre class="mb-0 p-3 bg-dark rounded-0" style="overflow-x: auto;"><code class="yaml hljs" id="configContent">{{ $node->getYamlConfiguration() }}</code></pre>
                    <div class="position-absolute top-0 end-0 p-2">
                        <button type="button" class="btn btn-sm btn-dark opacity-75" id="copyConfigBtn" data-bs-toggle="tooltip" title="Copy to Clipboard">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm border-top">
                <div class="alert alert-info d-flex">
                    <div class="flex-shrink-0">
                        <i class="fa fa-fw fa-info-circle"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-0">This file should be placed in your daemon's root directory (usually <code>/etc/pterodactyl</code>) in a file called <code>config.yml</code>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-rocket me-2"></i>Auto-Deploy
                </h3>
            </div>
            <div class="block-content">
                <p class="text-muted small">
                    Use the button below to generate a custom deployment command that can be used to configure
                    wings on the target server with a single command.
                </p>
            </div>
            <div class="block-content block-content-full block-content-sm text-center border-top">
                <button type="button" id="configTokenBtn" class="btn btn-alt-primary w-100">
                    <i class="fa fa-key me-1"></i> Generate Token
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    
    <!-- Highlight.js CSS and JS for syntax highlighting -->
    <link rel="stylesheet" href="{{ asset('themes/one-ui/js/plugins/highlightjs/styles/atom-one-dark.css') }}">
    <script src="{{ asset('themes/one-ui/js/plugins/highlightjs/highlight.pack.min.js') }}"></script>
    
    <script>
    // Initialize everything after DOM is ready using OneUI's proper initialization
    $(document).ready(function() {
        // Use OneUI's built-in helper for highlight.js initialization
        if (typeof One !== 'undefined' && typeof hljs !== 'undefined') {
            One.helpersOnLoad(['js-highlightjs']);
            console.log('OneUI highlight.js initialized successfully');
        } else if (typeof hljs !== 'undefined') {
            // Fallback to direct hljs initialization if OneUI not available
            if (!hljs.isHighlighted) {
                hljs.initHighlighting();
            }
            console.log('Direct highlight.js initialized successfully');
        } else {
            console.warn('Syntax highlighting not available - hljs library not found');
        }
    });
    
    // Copy to clipboard functionality
    $('#copyConfigBtn').on('click', function (event) {
        event.preventDefault();
        
        const button = $('#copyConfigBtn');
        const configText = $('#configContent').text();
        
        // Show loading state
        const originalContent = button.html();
        button.html('<i class="fa fa-spinner fa-spin"></i>');
        button.prop('disabled', true);
        
        // Use the modern Clipboard API if available
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(configText).then(function() {
                // Success
                showCopySuccess(button, originalContent);
                showNotification('success', 'Configuration copied to clipboard!');
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                fallbackCopyTextToClipboard(configText, button, originalContent);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(configText, button, originalContent);
        }
    });
    
    // Show copy success state
    function showCopySuccess(button, originalContent) {
        button.html('<i class="fa fa-check text-success"></i>');
        button.prop('disabled', false);
        
        setTimeout(function() {
            button.html(originalContent);
        }, 2000);
    }
    
    // Show notification with fallback
    function showNotification(type, message) {
        // Try OneUI notification first
        if (typeof One !== 'undefined' && One.helpers && One.helpers.jqNotify) {
            One.helpers.jqNotify({
                type: type === 'success' ? 'success' : 'danger',
                icon: type === 'success' ? 'fa fa-check me-1' : 'fa fa-times me-1',
                message: message
            });
        } 
        // Fallback to SweetAlert if available
        else if (typeof swal !== 'undefined') {
            swal({
                type: type,
                title: type === 'success' ? 'Success!' : 'Error!',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        }
        // Last resort: browser alert
        else {
            alert(message);
        }
    }
    
    // Fallback copy function for older browsers
    function fallbackCopyTextToClipboard(text, button, originalContent) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button, originalContent);
                showNotification('success', 'Configuration copied to clipboard!');
            } else {
                button.html(originalContent);
                button.prop('disabled', false);
                showNotification('error', 'Failed to copy to clipboard. Please copy manually.');
            }
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
            button.html(originalContent);
            button.prop('disabled', false);
            showNotification('error', 'Failed to copy to clipboard. Please copy manually.');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Generate token functionality (existing)
    $('#configTokenBtn').on('click', function (event) {
        const button = $(this);
        const originalContent = button.html();
        
        // Show loading state
        button.html('<i class="fa fa-spinner fa-spin me-1"></i> Generating...');
        button.prop('disabled', true);
        
        $.ajax({
            method: 'POST',
            url: '{{ route('admin.nodes.view.configuration.token', $node->id) }}',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        }).done(function (data) {
            swal({
                type: 'success',
                title: 'Token created.',
                text: '<p>To auto-configure your node run the following command:<br /><small><pre>cd /etc/pterodactyl && sudo wings configure --panel-url {{ config('app.url') }} --token ' + data.token + ' --node ' + data.node + '{{ config('app.debug') ? ' --allow-insecure' : '' }}</pre></small></p>',
                html: true
            });
        }).fail(function () {
            swal({
                title: 'Error',
                text: 'Something went wrong creating your token.',
                type: 'error'
            });
        }).always(function() {
            // Restore button state
            button.html(originalContent);
            button.prop('disabled', false);
        });
    });
    </script>
@endsection
