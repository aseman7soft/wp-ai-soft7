jQuery(document).ready(function($) {
    'use strict';
    
    // Tab Navigation
    $('.aseman-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.aseman-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.aseman-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Provider Mode Toggle
    $('#provider_mode').on('change', function() {
        if ($(this).val() === 'remote') {
            $('#remote-settings').show();
            $('#local-settings').hide();
        } else {
            $('#remote-settings').hide();
            $('#local-settings').show();
        }
    }).trigger('change');
    
    // Test Connection
    $('#test-connection-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $result = $('#test-connection-result');
        
        $btn.prop('disabled', true).text(asemanRobotAjax.strings.testing);
        $result.removeClass('success error').text('');
        
        $.ajax({
            url: asemanRobotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aseman_robot_test_connection',
                nonce: asemanRobotAjax.nonce,
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Test Connection');
                
                if (response.success) {
                    $result.addClass('success').text(asemanRobotAjax.strings.success + ' ' + response.data.message);
                } else {
                    $result.addClass('error').text(asemanRobotAjax.strings.failed + ' ' + response.data.message);
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Test Connection');
                $result.addClass('error').text(asemanRobotAjax.strings.failed + ' Network error');
            }
        });
    });
    
    // Retry Job
    $('.aseman-retry-job').on('click', function(e) {
        e.preventDefault();
        
        var jobId = $(this).data('job-id');
        var $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: asemanRobotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aseman_robot_retry_job',
                nonce: asemanRobotAjax.nonce,
                job_id: jobId
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Network error', 'error');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Cancel Job
    $('.aseman-cancel-job').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(asemanRobotAjax.strings.confirm_cancel)) {
            return;
        }
        
        var jobId = $(this).data('job-id');
        var $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: asemanRobotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aseman_robot_cancel_job',
                nonce: asemanRobotAjax.nonce,
                job_id: jobId
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Network error', 'error');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Delete Job
    $('.aseman-delete-job').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(asemanRobotAjax.strings.confirm_delete)) {
            return;
        }
        
        var jobId = $(this).data('job-id');
        var $btn = $(this);
        var $row = $btn.closest('tr');
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: asemanRobotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aseman_robot_delete_job',
                nonce: asemanRobotAjax.nonce,
                job_id: jobId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() {
                        $(this).remove();
                    });
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Network error', 'error');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Clone as Force Generate
    $('.aseman-clone-force-job').on('click', function(e) {
        e.preventDefault();
        
        var jobId = $(this).data('job-id');
        var $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: asemanRobotAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'aseman_robot_clone_force_job',
                nonce: asemanRobotAjax.nonce,
                job_id: jobId
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(response.data.message, 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Network error', 'error');
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Show Message Helper
    function showMessage(message, type) {
        var $messageDiv = $('#aseman-ajax-message');
        
        if ($messageDiv.length === 0) {
            $messageDiv = $('<div id="aseman-ajax-message"></div>').appendTo('.wrap');
        }
        
        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .show();
        
        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 5000);
    }
    
    // Publish Mode Toggle
    $('#publish_mode').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#schedule-row, #interval-row').show();
        } else {
            $('#schedule-row, #interval-row').hide();
        }
    });
});
