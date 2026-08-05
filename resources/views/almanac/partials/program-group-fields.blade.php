<label>Group Name</label><input name="name" class="form-control mb-2" required>
<label>Level</label><input name="level" class="form-control mb-2">
<label>Display Order</label><input type="number" name="display_order" class="form-control mb-2" min="1" value="{{ $setup->programGroups->count()+1 }}" required>
<div class="row"><div class="col"><label>Background</label><input type="color" name="background_color" class="form-control form-control-color" value="#dbeafe"></div><div class="col"><label>Text</label><input type="color" name="text_color" class="form-control form-control-color" value="#000000"></div></div>
<label class="mt-2">Programs</label><select name="program_ids[]" class="form-select" multiple size="6">@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
<div class="form-check mt-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active</label></div>
