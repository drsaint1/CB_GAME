@props(['disabled' => false])

<div class="form-control-wrap">
    <div class="form-control-select">
        <select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'form-control']) !!}>
            {{ $slot }}
        </select>
    </div>
</div>

{{-- <div class="form-control-wrap">
    <div class="form-control-select">
        <select class="form-control" id="gender" name="gender">
            <option value="male" {{ old('gender') == 'male' ? ' selected ' : '' }}>Male</option>
            <option value="female" {{ old('gender') == 'female' ? ' selected ' : '' }}>Female</option>
        </select>
        @error('gender')
            <span class="invalid">{{ $message }}</span>
        @enderror
    </div>
</div> --}}