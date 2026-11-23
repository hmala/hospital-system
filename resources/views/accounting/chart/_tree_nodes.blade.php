@foreach($accounts as $acc)
    <div class="tree-node-item my-1" data-code="{{ $acc->code }}" data-name="{{ $acc->name }}">
        <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 hover-bg-light cursor-pointer tree-node border-bottom border-light"
             onclick="{{ $acc->children->count() > 0 ? "toggleNode('node-{$acc->id}', this)" : "selectAccount({$acc->id})" }}"
             data-account-id="{{ $acc->id }}">
            <div class="d-flex align-items-center gap-2">
                @if($acc->children->count() > 0)
                    <span class="tree-toggle-icon text-muted" style="width: 14px;">
                        <i class="fas fa-chevron-left small" id="icon-node-{{ $acc->id }}"></i>
                    </span>
                @else
                    <span style="width: 14px;" class="text-center">
                        <i class="fas fa-circle text-success" style="font-size: 6px;"></i>
                    </span>
                @endif

                <span class="font-monospace fw-bold {{ $acc->is_postable ? 'text-success' : 'text-secondary' }} small">
                    {{ $acc->code }}
                </span>
                <span class="small text-dark {{ $acc->is_postable ? 'fw-normal' : 'fw-semibold' }}">
                    {{ $acc->name }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-1">
                @if($acc->is_postable)
                    <span class="badge bg-success-subtle text-success small" style="font-size: 10px;">يقبل القيود</span>
                @else
                    <span class="badge bg-light text-muted border small" style="font-size: 10px;">{{ $acc->children->count() }} فرع</span>
                @endif
                <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 px-1 text-primary small" onclick="event.stopPropagation(); selectAccount({{ $acc->id }})">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        @if($acc->children->count() > 0)
            <div class="tree-children ms-3 d-none border-start border-2 border-slate-200 ps-2" id="node-{{ $acc->id }}">
                @include('accounting.chart._tree_nodes', ['accounts' => $acc->children])
            </div>
        @endif
    </div>
@endforeach
