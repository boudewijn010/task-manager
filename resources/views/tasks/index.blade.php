@extends('layout')

@section('title', 'Task List')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-list"></i> My Tasks</h1>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Task
            </a>
        </div>

        <!-- Filter buttons -->
        <div class="mb-3">
            <div class="btn-group" role="group">
                <a href="{{ route('tasks.index') }}" 
                   class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">
                    All Tasks
                </a>
                <a href="?status=pending" 
                   class="btn {{ request('status') == 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                    Pending
                </a>
                <a href="?status=in_progress" 
                   class="btn {{ request('status') == 'in_progress' ? 'btn-info' : 'btn-outline-info' }}">
                    In Progress
                </a>
                <a href="?status=completed" 
                   class="btn {{ request('status') == 'completed' ? 'btn-success' : 'btn-outline-success' }}">
                    Completed
                </a>
            </div>
        </div>

        @if($tasks->count() > 0)
            <div class="row">
                @foreach($tasks as $task)
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">{{ $task->title }}</h5>
                                    <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted">
                                    {{ Str::limit($task->description, 100) }}
                                </p>

                                <div class="mb-3">
                                    <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in_progress' ? 'info' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>

                                @if($task->due_date)
                                    <p class="card-text">
                                        <small class="text-{{ $task->isOverdue() ? 'danger' : 'muted' }}">
                                            <i class="fas fa-calendar"></i>
                                            Due: {{ $task->due_date->format('M d, Y') }}
                                        </small>
                                    </p>
                                @endif

                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    
                                    <div>
                                        @if(!$task->isCompleted())
                                            <form method="POST" action="{{ route('tasks.complete', $task) }}" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this task?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer text-muted">
                                <small>Created {{ $task->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $tasks->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-tasks fa-5x text-muted mb-3"></i>
                <h3>No Tasks Found</h3>
                <p class="text-muted">Start by creating your first task!</p>
                <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Task
                </a>
            </div>
        @endif
    </div>
</div>
@endsection