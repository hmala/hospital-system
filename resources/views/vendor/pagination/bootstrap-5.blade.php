@if ($paginator->hasPages())
    <nav aria-label="التنقل بين الصفحات">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            {{-- معلومات الصفحات --}}
            <div class="text-muted small">
                عرض <span class="fw-bold text-primary">{{ $paginator->firstItem() ?? 0 }}</span>
                إلى <span class="fw-bold text-primary">{{ $paginator->lastItem() ?? 0 }}</span>
                من <span class="fw-bold text-primary">{{ $paginator->total() }}</span> نتيجة
            </div>

            {{-- أزرار التنقل --}}
            <ul class="pagination mb-0">
                {{-- زر الصفحة الأولى --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link" aria-hidden="true">
                            <i class="fas fa-angle-double-right"></i>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url(1) }}" title="الصفحة الأولى">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                @endif

                {{-- زر السابق --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link" aria-hidden="true">
                            <i class="fas fa-angle-right"></i>
                            السابق
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                            <i class="fas fa-angle-right"></i>
                            السابق
                        </a>
                    </li>
                @endif

                {{-- أرقام الصفحات --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled">
                            <span class="page-link">{{ $element }}</span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- زر التالي --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                            التالي
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link" aria-hidden="true">
                            التالي
                            <i class="fas fa-angle-left"></i>
                        </span>
                    </li>
                @endif

                {{-- زر الصفحة الأخيرة --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" title="الصفحة الأخيرة">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link" aria-hidden="true">
                            <i class="fas fa-angle-double-left"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>

    <style>
        .pagination {
            direction: ltr;
        }
        .pagination .page-link {
            padding: 0.5rem 0.85rem;
            font-size: 0.9rem;
            border-color: #dee2e6;
            color: #6c757d;
        }
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #0d6efd;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
        }
        .pagination .page-link i {
            font-size: 0.8rem;
        }
    </style>
@endif

