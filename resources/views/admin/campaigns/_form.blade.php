@csrf
<div class="row g-3">
    <div class="col-md-8"><label class="form-label">{{ __('messages.lg_name') }}</label><input name="name" class="form-control" required
            value="{{ old('name', $campaign->name) }}"></div>
    <div class="col-md-4"><label class="form-label">{{ __('messages.lg_minimum_score') }}</label><input name="minimum_score" type="number"
            min="0" max="100" class="form-control"
            value="{{ old('minimum_score', $campaign->minimum_score ?? 40) }}"></div>
    <div class="col-12"><label class="form-label">{{ __('messages.lg_description') }}</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $campaign->description) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_countries') }}</label>
        <textarea name="countries" class="form-control" rows="4">{{ old('countries', implode("\n", $campaign->countries ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_cities') }}</label>
        <textarea name="cities" class="form-control" rows="4">{{ old('cities', implode("\n", $campaign->cities ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_industries') }}</label>
        <textarea name="industries" class="form-control" rows="4">{{ old('industries', implode("\n", $campaign->industries ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_services') }}</label>
        <textarea name="services" class="form-control" rows="4">{{ old('services', implode("\n", $campaign->services ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_keywords') }}</label>
        <textarea name="keywords" class="form-control" rows="4">{{ old('keywords', implode("\n", $campaign->keywords ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">{{ __('messages.lg_negative_keywords') }}</label>
        <textarea name="negative_keywords" class="form-control" rows="4">{{ old('negative_keywords', implode("\n", $campaign->negative_keywords ?: [])) }}</textarea>
    </div>
    <div class="col-12 form-check ms-2"><input type="checkbox" name="enabled" value="1" class="form-check-input"
            @checked(old('enabled', $campaign->enabled ?? true))><label class="form-check-label">{{ __('messages.lg_enabled') }}</label></div>
    <div class="col-12 d-flex gap-2"><button class="btn-primary-sm"><i class="bi bi-save"></i> {{ __('messages.lg_save') }}</button><a
            href="{{ route('admin.campaigns.index') }}" class="btn-outline-sm">{{ __('messages.lg_cancel') }}</a></div>
</div>
