<?php

namespace App\Livewire;

use App\Models\LinkRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    public function delete(int $id): void
    {
        LinkRequest::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->delete();
    }

    public function clear(): void
    {
        LinkRequest::query()->where('user_id', Auth::id())->delete();
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();
        Auth::logout();
        $user?->delete();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/');
    }

    public function render()
    {
        return view('livewire.history', [
            'requests' => LinkRequest::query()
                ->where('user_id', Auth::id())
                ->latest('updated_at')
                ->paginate(12),
        ])->layout('components.layouts.app');
    }
}
