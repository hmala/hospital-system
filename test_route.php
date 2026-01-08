Route::get('/test-cashier-data', function() {
    $pendingRequests = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
        ->where('payment_status', 'pending')
        ->whereHas('visit', function($q) {
            $q->where('status', '!=', 'cancelled');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15, ['*'], 'requests_page');
    
    return view('test-cashier-view', compact('pendingRequests'));
});