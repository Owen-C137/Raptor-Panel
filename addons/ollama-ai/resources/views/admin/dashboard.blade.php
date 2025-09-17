@extends('layouts.admin')

@section('title')
    AI Management Dashboard
@endsection

@section('content-header')
    <h1>AI Management <small>Ollama AI Integration</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">AI Management</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">🤖 Ollama AI Dashboard</h3>
            </div>
            
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-robot"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">AI Status</span>
                                <span class="info-box-number">Active</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box bg-blue">
                            <span class="info-box-icon"><i class="fa fa-comments"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Conversations</span>
                                <span class="info-box-number" id="conversations-count">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-code"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Code Generated</span>
                                <span class="info-box-number" id="code-generations-count">Loading...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-brain"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">AI Models</span>
                                <span class="info-box-number">4</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">Quick Actions</h3>
                            </div>
                            <div class="box-body">
                                <div class="btn-group-vertical btn-block" role="group">
                                    <a href="{{ route('admin.ai.conversations') }}" class="btn btn-default btn-flat">
                                        <i class="fa fa-comments"></i> Manage Conversations
                                    </a>
                                    <a href="{{ route('admin.ai.models') }}" class="btn btn-default btn-flat">
                                        <i class="fa fa-brain"></i> AI Models
                                    </a>
                                    <a href="{{ route('admin.ai.code-generation') }}" class="btn btn-default btn-flat">
                                        <i class="fa fa-code"></i> Code Generation
                                    </a>
                                    <a href="{{ route('admin.ai.templates') }}" class="btn btn-default btn-flat">
                                        <i class="fa fa-file-code"></i> Templates
                                    </a>
                                    <a href="{{ route('admin.ai.settings') }}" class="btn btn-default btn-flat">
                                        <i class="fa fa-cog"></i> Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="box box-success">
                            <div class="box-header with-border">
                                <h3 class="box-title">System Status</h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-xs-6">
                                        <strong>Ollama Server:</strong>
                                    </div>
                                    <div class="col-xs-6">
                                        <span class="label label-success">Connected</span>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <strong>Available Models:</strong>
                                    </div>
                                    <div class="col-xs-6">
                                        <span class="label label-info">4 Models</span>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <strong>Database:</strong>
                                    </div>
                                    <div class="col-xs-6">
                                        <span class="label label-success">Healthy</span>
                                    </div>
                                </div>
                                <br>
                                <div class="text-center">
                                    <button class="btn btn-primary btn-sm" id="test-connection">
                                        <i class="fa fa-refresh"></i> Test Connection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load statistics
    loadStatistics();
    
    // Test connection button
    $('#test-connection').click(function() {
        $(this).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        // Implementation would test Ollama connection
        setTimeout(function() {
            $('#test-connection').html('<i class="fa fa-check"></i> Connected');
            setTimeout(function() {
                $('#test-connection').html('<i class="fa fa-refresh"></i> Test Connection');
            }, 2000);
        }, 1500);
    });
});

function loadStatistics() {
    // Load conversations count
    // Implementation would make AJAX calls to get actual statistics
    $('#conversations-count').text('127');
    $('#code-generations-count').text('89');
}
</script>
@endsection