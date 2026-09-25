<section class="teacher-photo-editor" aria-labelledby="teacher-photo-heading">
    <h2 id="teacher-photo-heading">Profile Photo</h2>
    @if (session('status') === 'teacher-photo-updated')<p class="teacher-photo-status" role="status">Profile photo saved.</p>@endif
    @if (session('status') === 'teacher-photo-removed')<p class="teacher-photo-status" role="status">Profile photo removed.</p>@endif
    <form method="POST" action="{{ route('teacher.photo.store') }}" enctype="multipart/form-data" x-data="teacherPhotoUpload" class="teacher-photo-form" data-teacher-photo-form>
        @csrf
        <div class="teacher-photo-preview">
            <template x-if="preview"><img :src="preview" alt="New profile photo preview" width="128" height="128"></template>
            <div x-show="!preview"><x-teacher-avatar :teacher="$user" size="lg" /></div>
        </div>
        <div class="teacher-photo-fields">
            <label for="teacher-photo">Choose photo</label>
            <input id="teacher-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required @change="selectPhoto($event)" aria-describedby="teacher-photo-limits teacher-photo-error">
            <p id="teacher-photo-limits">JPG, PNG or WebP. Up to 2 MB; 64&ndash;4096 pixels per side.</p>
            <div id="teacher-photo-error"><x-input-error :messages="$errors->teacherPhoto->get('photo')" /></div>
            <button class="ui-button" type="submit"><span class="material-symbols-outlined" aria-hidden="true">upload</span>Save Photo</button>
        </div>
    </form>
    @if ($user->teacher_photo_path)
        <form method="POST" action="{{ route('teacher.photo.destroy') }}" class="teacher-photo-remove">
            @csrf @method('DELETE')
            <button class="ui-button ui-button-secondary" type="submit"><span class="material-symbols-outlined" aria-hidden="true">delete</span>Remove Photo</button>
        </form>
    @endif
</section>
