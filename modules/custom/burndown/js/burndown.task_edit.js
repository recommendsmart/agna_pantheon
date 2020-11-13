/**
 * @file
 * Contains burndown.task_edit.js.
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.burndownTaskEdit = {
    attach: function (context, settings) {
      // Only do setup once.
      $('body').once('setupLogs').each(function () {
        update_log();
        update_relationships();
      });

      // Make the watch/unwatch task link work.
      $('.watch_list')
        .once('watchListAction')
        .on('click', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          // Get the link url.
          var url = $(e.target).attr('href');

          // Send a GET.
          $.ajax({
              url: url, 
              method :'GET', 
              success: function(result){
                // Switch the class and url.
                var container = $('.watch_list');
                var my_link = $('.watch_list a');
                var url = my_link.attr('href');

                if (url.includes('remove_from_watchlist')) {
                  my_link.text('Watch this task');
                  url = url.replace('remove_from_watchlist', 'add_to_watchlist');
                  my_link.attr('href', url);
                  container.removeClass('watch').addClass('mute');
                }
                else {
                  my_link.text('Stop watching this task');
                  url = url.replace('add_to_watchlist', 'remove_from_watchlist');
                  my_link.attr('href', url);
                  container.removeClass('mute').addClass('watch');
                }
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Watchlist follow/unfollow error.");
              }
          });
        });

      // For modal views of the task edit form,
      // pull log data when the log details container
      // is opened.
      $('body')
        .once('updateLogAction')
        .on('click', '[data-drupal-selector="edit-log"] summary', function (e) {
          update_log('comment');
        });

      // Similarly, load relationships when the tab is opened.
      $('body')
        .once('updateRelationshipsAction')
        .on('click', '[data-drupal-selector="edit-relationships-wrapper"] summary', function (e) {
          update_relationships();
        });

      // Tabs to control which logs show.
      $('body')
        .once('clickTabsAction')
        .on('click', '.log_tabs a', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          var log_type = $(e.target).attr('class');
          update_log(log_type);

          if (log_type == 'comment') {
            // Show comment field.
            $('.form-wrapper.add_comment').show();

            // Hide work field.
            $('.form-wrapper.add_work').hide();
          }
          else if (log_type == 'work') {
            // Show work field.
            $('.form-wrapper.add_work').show();

            // Hide comment field.
            $('.form-wrapper.add_comment').hide();
          }
          else {
            // Hide both comment and work fields.
            $('.form-wrapper.add_comment').hide();
            $('.form-wrapper.add_work').hide();
          }
        });

      // Posting a comment.
      $('body')
        .once('postCommentAction')
        .on('click', '.add_comment a.button', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          // Post data.
          $.ajax({
              url: "/burndown/api/task/add_comment", 
              method :'POST',
              data: { 
                ticket_id: $('#burndown_task_log').data('ticket-id'),
                comment: $('.add_comment .form-item-body textarea').val()
              },
              success: function(result){
                // On success, reload comments and clear the form.
                update_log('comment');
                $('.add_comment .form-item-body textarea').val('');
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Could not post comment.");
              }
          });
        });

      // Posting a work log.
      $('body')
        .once('postWorkAction')
        .on('click', '.add_work a.button', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          // Post data.
          $.ajax({
              url: "/burndown/api/task/add_work", 
              method :'POST',
              data: { 
                ticket_id: $('#burndown_task_log').data('ticket-id'),
                comment: $('.add_work .form-item-body textarea').val(),
                work: $('.add_work .form-item-quantity input').val(),
                work_increment: $('.add_work .form-item-quantity-type select').val()
              },
              success: function(result){
                // On success, reload work and clear the form.
                update_log('work');
                $('.add_work .form-item-body textarea').val('');
                $('.add_work .form-item-quantity input').val('');
                $('.add_work .form-item-quantity-type select').val('h');
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Could not post comment.");
              }
          });
        });
 
      // Add a relationship.
      // POST to 
      $('body')
        .once('postRelationshipAction')
        .on('click', 'a.button.add_relationship', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          var from_ticket_id = $(e.target).data('ticket-id');
          var to_ticket_id = $('.add_relationship .form-item-to-task input').val();
          to_ticket_id = to_ticket_id.split(" ");
          to_ticket_id = to_ticket_id[0];

          // Post data.
          $.ajax({
              url: "/burndown/api/task/add_relationship", 
              method :'POST',
              data: {
                from_ticket_id: from_ticket_id,
                to_ticket_id: to_ticket_id,
                type: $('.add_relationship .form-item-type select').val()
              },
              success: function(result){
                // On success, reload work and clear the form.
                update_relationships('work');
                $('.add_relationship .form-item-to-task input').val('');
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Could not post relationship.");
              }
          });
        });

      // Remove a relationship.
      $('body')
        .once('removeRelationshipAction')
        .on('click', 'a.remove_relationship', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          // Confirm.
          var ret = confirm("Are you SURE that you want to remove this relationship?");
          if (!ret) {
            return;
          }

          // Get ticket info.
          var relationship = $(e.target).parent().parent();
          var from_ticket_id = relationship.data('from-ticket-id');
          var to_ticket_id = relationship.data('to-ticket-id');

          // Send data.
          $.ajax({
              url: "/burndown/api/task/remove_relationship/" + from_ticket_id + "/" + to_ticket_id, 
              method :'GET',
              success: function(result){
                // On success, reload relationships.
                update_relationships();
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log("Could not remove relationship.");
              }
          });
        });

      // Assign to me link.
      $('body')
        .once('assignToMeLink')
        .on('click', 'a.assign_to_me', function (e) {
          // Do not follow the link.
          e.preventDefault();
          e.stopPropagation();

          // Get user info from drupalSettings.
          var user = drupalSettings.user;

          // Set the user entity reference field.
          if (user.hasOwnProperty('name')) {
            var user_name = user.name + ' (' + user.uid + ')';
            $('.field--name-assigned-to input.form-autocomplete').val(user_name);
          }
        });

      function update_log(type) {
        // Get the ticket id.
        var ticket_id = $('#burndown_task_log').data('ticket-id');

        // AJAX path.
        var path = "/burndown/api/task_log/" + ticket_id + '/' + type;

        // Update our log.
        $('#burndown_task_log').load(path);
      }

      function update_relationships() {
        // Get the ticket id.
        var ticket_id = $('#burndown_task_log').data('ticket-id');

        // AJAX path.
        var path = '/burndown/api/task/get_relationships/' + ticket_id;

        // Update our log.
        $('#relationships_list').load(path);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
