<div class="form-group">
    <label for="owl_species">Species</label>
    <input type="text" name="owl_species" id="owl_species" class="form-control" value="<?php echo esc_attr($selected_species); ?>" required>
</div>

<div class="form-group">
    <label for="owl_location">Location</label>
    <input type="text" name="owl_location" id="owl_location" class="form-control" value="<?php echo esc_attr($location); ?>" required>
</div>

<div class="form-group">
    <label for="owl_date_spotted">Date Spotted</label>
    <input type="date" name="owl_date_spotted" id="owl_date_spotted" class="form-control" value="<?php echo esc_attr($date_spotted); ?>" required>
</div>

<div class="form-group">
    <label for="owl_notes">Notes</label>
    <textarea name="owl_notes" id="owl_notes" class="form-control" rows="4"><?php echo esc_textarea($notes); ?></textarea>
</div>
