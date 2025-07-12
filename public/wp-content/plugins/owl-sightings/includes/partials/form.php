<?php
$pnw_owls = get_pnw_owls();
?>

<!-- HTML form for owl sighting submission -->
<form method="post" enctype="multipart/form-data" id="owl-sighting-form" class="owl-form">
    <?php wp_nonce_field('submit_owl_sighting', 'owl_sighting_nonce'); ?>

    <!-- Owl species dropdown with More Info button -->
    <div class="form-group">
        <label for="owl_species">Species</label>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <select name="owl_species" id="owl_species" class="form-control" required style="flex: 1;">
                <option value="">-- Select an Owl --</option>
                <?php foreach ($pnw_owls as $owl): ?>
                    <option value="<?php echo esc_attr($owl['name']); ?>"
                        data-protected="<?php echo $owl['protected'] ? '1' : '0'; ?>">
                        <?php echo esc_html($owl['name']); ?>
                        <?php if ($owl['protected']) echo ' (protected)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="lookup-owl" class="btn">🔍 More Info</button>
        </div>
    </div>

    <!-- Wikipedia preview container -->
    <div id="wiki-result" style="margin-top: 1rem; border: 1px solid #ccc; padding: 1em; display: none;"></div>
    <input type="hidden" name="owl_protected" id="protected_species" value="0">

    <!-- Location field (county dropdown) -->
    <div class="form-group">
        <label for="owl_location">County (Washington only)</label>
        <select name="owl_location" id="owl_location" class="form-control" required>
            <option value="">-- Select a County --</option>
            <?php
            $washington_counties = get_washington_counties();
            foreach ($washington_counties as $county) {
                echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . ' County</option>';
            }
            ?>
        </select>
    </div>

    <!-- Date field -->
    <div class="form-group">
        <label for="owl_date_spotted">Date Spotted</label>
        <input type="date" name="owl_date_spotted" id="owl_date_spotted" class="form-control" required max="<?php echo date('Y-m-d'); ?>">
    </div>

    <!-- Notes field -->
    <div class="form-group">
        <label for="owl_notes">Notes</label>
        <textarea name="owl_notes" id="owl_notes" class="form-control" rows="4"></textarea>
    </div>

    <!-- Photo upload field -->
    <div class="form-group">
        <label for="owl_photo">Photo</label>
        <input type="file" name="owl_photo" id="owl_photo" class="form-control">
    </div>

    <!-- Submit button -->
    <div class="form-actions">
        <button type="submit" class="btn">Submit Sighting</button>
    </div>

    <!-- Side panel (JavaScript-driven Wikipedia display) -->
    <div id="owl_side_panel">
        <div id="owl_side_panel_inner">
            <button id="close_owl_panel">&times;</button>
            <div id="owl_panel_spinner">Loading info...</div>
            <div id="owl_panel_content" style="display: none;">
                <img id="owl_panel_img" src="" alt="" />
                <div id="owl_panel_summary"></div>
                <a id="owl_panel_link" href="#" target="_blank">Read more on Wikipedia</a>
            </div>
        </div>
    </div>
</form>