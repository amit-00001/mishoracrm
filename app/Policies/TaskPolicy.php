<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($user->tenant_id !== $task->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('tasks.view_all')) {
            return true;
        }

        return $user->can('tasks.view_own') && $task->assigned_to === $user->id;
    }

    public function modify(User $user, Task $task): bool
    {
        if ($user->tenant_id !== $task->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('tasks.edit_all')) {
            return true;
        }

        return $user->can('tasks.edit_own') && $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->modify($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->modify($user, $task);
    }
}
