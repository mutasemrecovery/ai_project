@csrf
<div class="row g-3">
    <div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" required
            value="{{ old('name', $campaign->name) }}"></div>
    <div class="col-md-4"><label class="form-label">Minimum Score</label><input name="minimum_score" type="number"
            min="0" max="100" class="form-control"
            value="{{ old('minimum_score', $campaign->minimum_score ?? 40) }}"></div>
    <div class="col-12"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $campaign->description) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Countries</label>
        <textarea name="countries" class="form-control" rows="4">{{ old('countries', implode("\n", $campaign->countries ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Cities</label>
        <textarea name="cities" class="form-control" rows="4">{{ old('cities', implode("\n", $campaign->cities ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Industries</label>
        <textarea name="industries" class="form-control" rows="4">{{ old('industries', implode("\n", $campaign->industries ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Services</label>
        <textarea name="services" class="form-control" rows="4">{{ old('services', implode("\n", $campaign->services ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Keywords</label>
        <textarea name="keywords" class="form-control" rows="4">{{ old('keywords', implode("\n", $campaign->keywords ?: [])) }}</textarea>
    </div>
    <div class="col-md-6"><label class="form-label">Negative Keywords</label>
        <textarea name="negative_keywords" class="form-control" rows="4">{{ old('negative_keywords', implode("\n", $campaign->negative_keywords ?: [])) }}</textarea>
    </div>
    <div class="col-12 form-check ms-2"><input type="checkbox" name="enabled" value="1" class="form-check-input"
            @checked(old('enabled', $campaign->enabled ?? true))><label class="form-check-label">Enabled</label></div>
    <div class="col-12 d-flex gap-2"><button class="btn-primary-sm"><i class="bi bi-save"></i> Save</button><a
            href="{{ route('admin.campaigns.index') }}" class="btn-outline-sm">Cancel</a></div>
</div>
