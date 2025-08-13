<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Stat extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $label,
        public string|int $value,
        public string $icon = 'circle',
        public string $color = 'slate'
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.stat');
    }
}
