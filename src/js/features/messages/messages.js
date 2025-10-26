/*
 * Messages feature
 * Modularised versions of legacy Racketmanager.getMessage / deleteMessage / deleteMessages
 */

import { handleAjaxError, initializeAjaxError } from '../ajax/handle-ajax-error.js';
import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';

/**
 * Fetch and display a single message
 * @param {Event} event
 * @param {number|string} messageId
 */
export function getMessage(event, messageId) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }
  const messageDisplay = '#messageDetailDisplay';
  jQuery(messageDisplay).addClass('is-loading');
  jQuery('.selected').removeClass('selected');
  const messageRef = `#message-summary-${messageId}`;
  jQuery(messageRef).addClass('selected read');
  jQuery(messageRef).removeClass('unread');
  const notifyField = '#message_detail';
  jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
  jQuery(notifyField).val('');
  jQuery(notifyField).hide();
  const errorField = '#messagesAlert';
  const errorResponse = '#messagesAlertResponse';
  jQuery(errorField).hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: {
      message_id: messageId,
      action: 'racketmanager_get_message',
      security: getAjaxNonce(),
    },
    success: function (response) {
      const data = response.data;
      jQuery(notifyField).empty();
      jQuery(notifyField).html(data.output);
      if (data.status === '1') {
        const readMessagesRef = '#read-messages';
        let readMessages = jQuery(readMessagesRef).html();
        readMessages++;
        jQuery(readMessagesRef).html(readMessages);
        const unreadMessagesRef = '#unread-messages';
        let unreadMessages = jQuery(unreadMessagesRef).html();
        unreadMessages--;
        jQuery(unreadMessagesRef).html(unreadMessages);
      }
      jQuery(notifyField).show();
    },
    error: function (response) {
      handleAjaxError(response, errorResponse, errorField);
      jQuery(errorField).show();
    },
    complete: function () {
      jQuery(messageDisplay).removeClass('is-loading');
    },
  });
}

/**
 * Delete a single message
 * @param {Event} event
 * @param {number|string} messageId
 */
export function deleteMessage(event, messageId) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }
  if (confirm('Are you sure you want to delete this message?') !== true) {
    return;
  }
  jQuery('.selected').removeClass('selected');
  const messageRef = `#message-summary-${messageId}`;
  jQuery(messageRef).addClass('deleted');
  const notifyField = '#message_detail';
  jQuery(notifyField).removeClass('message-error');
  jQuery(notifyField).val('');
  jQuery(notifyField).hide();
  const errorField = '#messagesAlert';
  const errorResponse = '#messagesAlertResponse';
  jQuery(errorField).hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: {
      message_id: messageId,
      action: 'racketmanager_delete_message',
      security: getAjaxNonce(),
    },
    success: function (response) {
      jQuery(notifyField).empty();
      if (response.data.success !== false) {
        jQuery(messageRef).hide();
      }
      jQuery(notifyField).html(response.data.output);
      jQuery(notifyField).show();
    },
    error: function (response) {
      handleAjaxError(response, errorResponse, errorField);
      jQuery(errorField).show();
    },
  });
}

/**
 * Delete multiple messages via a form link/button
 * @param {Event} event
 * @param {HTMLAnchorElement|HTMLButtonElement} link - element inside a form
 */
export function deleteMessages(event, link) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }
  if (confirm('Are you sure you want to delete these messages?') !== true) {
    return;
  }
  const formId = `#${link.form.id}`;
  let form = jQuery(formId).serialize();
  form += '&action=racketmanager_delete_messages';
  jQuery('.selected').removeClass('selected');
  const notifyField = '#message_detail';
  const errorField = '#messagesAlert';
  const errorResponse = '#messagesAlertResponse';
  jQuery(errorField).hide();
  jQuery(notifyField).removeClass('message-error');
  jQuery(notifyField).val('');
  jQuery(notifyField).hide();

  jQuery.ajax({
    url: getAjaxUrl(),
    type: 'POST',
    data: form,
    success: function (response) {
      jQuery(notifyField).empty();
      if (response.data.success !== false) {
        const messagesRef = `.${response.data.type}`;
        jQuery(messagesRef).hide();
        const messageCountRef = `#${response.data.type}-messages`;
        const messageCount = 0;
        jQuery(messageCountRef).html(messageCount);
      }
      jQuery(notifyField).html(response.data.output);
      jQuery(notifyField).show();
    },
    error: function (response) {
      handleAjaxError(response, errorResponse, errorField);
      jQuery(errorField).show();
    },
  });
}

/**
 * Initialize the Messages feature and expose compatible globals.
 * This preserves backward compatibility with templates calling Racketmanager.getMessage()
 */
export function initializeMessages() {
  // Ensure global ajax error handler is bound
  initializeAjaxError();

  // Delegated handlers following architecture guidelines
  jQuery(document)
    // Open a message from the list
    .off('click.racketmanager.messages', '.message-summary')
    .on('click.racketmanager.messages', '.message-summary', function (e) {
      const messageId = this.dataset.messageId || jQuery(this).data('messageId');
      return getMessage(e, messageId);
    })
    // Delete a single message from the detail view
    .off('click.racketmanager.messages', '#deleteMessage')
    .on('click.racketmanager.messages', '#deleteMessage', function (e) {
      const messageId = this.dataset.msgId || jQuery(this).data('msgId');
      return deleteMessage(e, messageId);
    })
    // Delete multiple messages using the toolbar button
    .off('click.racketmanager.messages', '#deleteMessages')
    .on('click.racketmanager.messages', '#deleteMessages', function (e) {
      return deleteMessages(e, this);
    });
}
