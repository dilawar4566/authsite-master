<?php
// auth_form.php
?>
<div class="wrap">
    <h2>Authentication Settings</h2>
    <?php
    if (isset($_POST['authentication_status'])) {
        $authentication_status = sanitize_text_field($_POST['authentication_status']);
        if ($authentication_status === 'success') {
            $authentication_code = $_POST['authentication_data'];
    ?>
            <div class="notice notice-success is-dismissible auth-success-message">Authentication succeeded.</div>
        <?php
        } elseif ($authentication_status === 'failed') {
            $authentication_code = ' ';
        ?>
            <div class="notice notice-error is-dismissible auth-failed-message">Authentication failed. Please try again.</div>
    <?php
        }
    }
    ?>
    <form class="auth-settings-form" method="post" action="">
        <input class="auth-input" type="text" name="authentication_data" placeholder="Enter authentication data">
        <input class="auth-submit" type="submit" name="submit" value="Submit">
    </form>
</div>