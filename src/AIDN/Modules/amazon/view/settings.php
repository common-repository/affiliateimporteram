<div class="setting-content">
    <h3>Common setting</h3>
    <table class="form-table">
        <tr valign="top">
            <th scope="row" class="titledesc"><label for="aidn_amazon_default_site">Default site: </label></th>
            <td class="forminp forminp-select">
                <?php $cur_aidn_amazon_default_site = get_option('aidn_amazon_default_site', 'com'); ?>
                <select name="aidn_amazon_default_site" id="aidn_amazon_default_site">
                    <option value="com" <?php if ($cur_aidn_amazon_default_site == "com"): ?>selected="selected"<?php endif; ?>>com</option>
                    <option value="de" <?php if ($cur_aidn_amazon_default_site == "de"): ?>selected="selected"<?php endif; ?>>de</option>
                    <option value="co.uk" <?php if ($cur_aidn_amazon_default_site == "co.uk"): ?>selected="selected"<?php endif; ?>>co.uk</option>
                    <option value="ca" <?php if ($cur_aidn_amazon_default_site == "ca"): ?>selected="selected"<?php endif; ?>>ca</option>
                    <option value="fr" <?php if ($cur_aidn_amazon_default_site == "fr"): ?>selected="selected"<?php endif; ?>>fr</option>
                    <option value="co.jp" <?php if ($cur_aidn_amazon_default_site == "co.jp"): ?>selected="selected"<?php endif; ?>>co.jp</option>
                    <option value="it" <?php if ($cur_aidn_amazon_default_site == "it"): ?>selected="selected"<?php endif; ?>>it</option>
                    <option value="cn" <?php if ($cur_aidn_amazon_default_site == "cn"): ?>selected="selected"<?php endif; ?>>cn</option>
                    <option value="es" <?php if ($cur_aidn_amazon_default_site == "es"): ?>selected="selected"<?php endif; ?>>es</option>
                    <option value="in" <?php if ($cur_aidn_amazon_default_site == "in"): ?>selected="selected"<?php endif; ?>>in</option>
                </select>
            </td>
        </tr>
    </table>
</div>