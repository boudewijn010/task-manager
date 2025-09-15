# Laravel Task Manager

A simple task management web application built with Laravel using the MVC (Model-View-Controller) architecture and object-oriented programming principles.

## Features

- **CRUD Operations**: Create, Read, Update, and Delete tasks
- **Task Filtering**: Filter tasks by status (Pending, In Progress, Completed)
- **Priority Levels**: Set task priority (Low, Medium, High)
- **Due Dates**: Set and track task due dates with overdue indicators
- **Responsive Design**: Mobile-friendly interface using Bootstrap
- **Status Management**: Easy task status updates and completion marking
- **Clean Architecture**: Follows Laravel's MVC pattern with proper separation of concerns

## Project Structure

### MVC Architecture

#### Models (`app/Models/`)
- **Task.php**: Eloquent model with business logic, scopes, and relationships
  - Contains constants for status and priority options
  - Includes helper methods like `isCompleted()` and `isOverdue()`
  - Implements query scopes for filtering

#### Views (`resources/views/`)
- **layout.blade.php**: Master template with navigation and alerts
- **tasks/index.blade.php**: Task listing with filtering and pagination  
- **tasks/create.blade.php**: Task creation form
- **tasks/show.blade.php**: Individual task details view
- **tasks/edit.blade.php**: Task editing form

#### Controllers (`app/Http/Controllers/`)
- **TaskController.php**: Handles all task-related HTTP requests
  - Full CRUD operations following RESTful conventions
  - Form validation and error handling
  - Status filtering and completion actions

### Object-Oriented Features

- **Encapsulation**: Business logic contained within model methods
- **Inheritance**: Views extend base layout template
- **Polymorphism**: Different view behaviors based on task status/priority
- **Abstraction**: Controller abstracts complex operations into simple methods

## Database Schema

### Tasks Table
```sql
- id (Primary Key)
- title (VARCHAR, Required)
- description (TEXT, Optional)  
- status (ENUM: pending, in_progress, completed)
- priority (ENUM: low, medium, high)
- due_date (DATETIME, Optional)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## Installation & Setup

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Configure database settings in .env file
   php artisan migrate
   ```

4. **Run the Application**
   ```bash
   php artisan serve
   ```

5. **Access the Application**
   Open `http://localhost:8000` in your browser

## Usage

### Creating Tasks
1. Click "Add New Task" button
2. Fill in task details (title, description, status, priority, due date)
3. Submit the form

### Managing Tasks  
- **View**: Click the eye icon to see full task details
- **Edit**: Click the pencil icon to modify task information
- **Complete**: Click the checkmark to mark tasks as completed
- **Delete**: Click the trash icon to remove tasks (with confirmation)

### Filtering Tasks
Use the filter buttons to view:
- All tasks
- Pending tasks only
- In Progress tasks only  
- Completed tasks only

## Key Features Demonstration

### MVC Pattern
- **Model**: `Task` model handles data logic and database interactions
- **View**: Blade templates render the user interface
- **Controller**: `TaskController` processes user input and coordinates between Model and View

### Object-Oriented Programming
- **Classes**: Task model as a class with properties and methods
- **Methods**: Controller methods for each action (index, create, store, etc.)
- **Encapsulation**: Private business logic within model methods
- **Code Reuse**: Shared layout template and form validation

### Best Practices
- Form validation with error handling
- CSRF protection on all forms
- Responsive design with Bootstrap
- Clean URL structure with named routes
- Proper error messaging and user feedback

## Routes

### Web Routes
- `GET /` - Redirects to task list
- `GET /tasks` - Display all tasks
- `GET /tasks/create` - Show create form
- `POST /tasks` - Store new task
- `GET /tasks/{id}` - Show task details
- `GET /tasks/{id}/edit` - Show edit form
- `PUT /tasks/{id}` - Update task
- `DELETE /tasks/{id}` - Delete task
- `PATCH /tasks/{id}/complete` - Mark task complete

### API Routes (Future Enhancement)
RESTful API endpoints available under `/api/tasks` prefix for potential mobile app or AJAX integration.

## Technology Stack

- **Backend**: Laravel 10+ (PHP 8.1+)
- **Frontend**: Blade Templates, Bootstrap 5
- **Database**: MySQL/PostgreSQL/SQLite  
- **Icons**: Font Awesome
- **Architecture**: MVC Pattern
- **Programming Paradigm**: Object-Oriented Programming

This application demonstrates clean code architecture, separation of concerns, and modern web development practices using Laravel's elegant syntax and powerful features.