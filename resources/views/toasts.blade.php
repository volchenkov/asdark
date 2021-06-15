@if(request()->session()->has('msg'))
    <div class="toast"
         role="alert"
         aria-live="assertive"
         aria-atomic="true"
         data-autohide="true"
         data-delay="5000"
         style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <div class="toast-header bg-success text-light">
            <strong class="me-auto">Успешно</strong>
            <button type="button" class="ms-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body">
            {{ request()->session()->get('msg') }}
        </div>
    </div>
@endif

<script>
    $('.toast').toast('show')
</script>
