@if($errors->any())
    <div class="alert-error">
        <strong>Please fix the following:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-grid">
    <div class="field">
        <label for="name">Source Name</label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $source->name) }}"
            placeholder="Laravel Main Log"
            required
        >
    </div>

    <div class="field">
        <label for="type">Source Type</label>
        <select name="type" id="type" required>
            @foreach($sourceTypes as $key => $label)
                <option value="{{ $key }}" @selected(old('type', $source->type) === $key)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="parser">Parser</label>
        <select name="parser" id="parser">
            <option value="">Use source type</option>
            @foreach($sourceTypes as $key => $label)
                <option value="{{ $key }}" @selected(old('parser', $source->parser) === $key)>
                    {{ $key }}
                </option>
            @endforeach
        </select>
        <small>Only the Laravel parser is active right now.</small>
    </div>

    <div class="field">
        <label for="path">Log File Path</label>
        <input
            type="text"
            name="path"
            id="path"
            value="{{ old('path', $source->path) }}"
            placeholder="{{ storage_path('logs/laravel.log') }}"
            required
        >
        <small>
            Examples: /var/log/apache2/error.log, /var/log/nginx/access.log, /var/log/auth.log
        </small>
    </div>

    <div class="field">
        <label for="scan_interval_minutes">Scan Interval Minutes</label>
        <input
            type="number"
            name="scan_interval_minutes"
            id="scan_interval_minutes"
            value="{{ old('scan_interval_minutes', $source->scan_interval_minutes ?? 5) }}"
            min="1"
            max="1440"
            required
        >
    </div>

    <div class="field">
        <label for="retention_days">Retention Days</label>
        <input
            type="number"
            name="retention_days"
            id="retention_days"
            value="{{ old('retention_days', $source->retention_days ?? 30) }}"
            min="1"
            max="3650"
            required
        >
    </div>

    <div class="field checkbox-field">
        <label>
            <input
                type="checkbox"
                name="enabled"
                value="1"
                @checked(old('enabled', $source->exists ? $source->enabled : true))
            >
            Enabled
        </label>
    </div>
</div>

<div style="margin-top: 20px;">
    <button type="submit" class="button button-success">
        {{ $buttonText }}
    </button>

    <a href="{{ route('log-sentinel.sources.index') }}" class="button button-secondary">
        Cancel
    </a>
</div>
