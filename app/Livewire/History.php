<?php

namespace App\Livewire;

use App\Models\LinkRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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
        $search = trim($this->search);

        return view('livewire.history', [
            'requests' => LinkRequest::query()
                ->where('user_id', Auth::id())
                ->when($search !== '', function ($query) use ($search) {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where(function ($query) use ($term) {
                        $query->where('author_handle', 'like', $term)
                            ->orWhere('text_excerpt', 'like', $term)
                            ->orWhere('tweet_id', 'like', $term);
                    });
                })
                ->latest('updated_at')
                ->paginate(12),
        ])->layout('components.layouts.app', [
            'title' => __('app.history'),
            'description' => __('app.seo_history_description'),
            'robots' => 'noindex, nofollow',
        ]);
    }
}
