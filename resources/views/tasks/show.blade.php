@extends('layout')

@section('title', 'View Task')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-eye"></i> Task Details</h4>
                <div>
                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @if(!$task->isCompleted())
                        <form method="POST" action="{{ route('tasks.complete', $task) }}" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Mark Complete
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h2 class="mb-3">{{ $task->title }}</h2>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Status:</strong>
                                <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in_progress' ? 'info' : 'warning') }} ms-2">
                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Priority:</strong>
                                <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'secondary') }} ms-2">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </div>
                        </div>

                        @if($task->description)
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <div class="mt-2 p-3 bg-light rounded">
                                    {{ $task->description }}
                                </div>
                            </div>
                        @endif

                        @if($task->due_date)
                            <div class="mb-3">
                                <strong>Due Date:</strong>
                                <span class="ms-2 {{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">
                                    <i class="fas fa-calendar"></i>
                                    {{ $task->due_date->format('F j, Y g:i A') }}
                                    @if($task->isOverdue())
                                        <span class="badge bg-danger ms-2">OVERDUE</span>
                                    @endif
                                </span>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Created:</strong>
                                <span class="text-muted ms-2">
                                    {{ $task->created_at->format('F j, Y g:i A') }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Last Updated:</strong>
                                <span class="text-muted ms-2">
                                    {{ $task->updated_at->format('F j, Y g:i A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tasks
                </a>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display: inline;" 
                      onsubmit="return confirm('Are you sure you want to delete this task?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Task
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection