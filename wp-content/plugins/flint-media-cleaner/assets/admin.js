document.addEventListener('DOMContentLoaded', function () {
  var masterToggles = document.querySelectorAll('.wpuc-master-toggle');
  var quickToggles = document.querySelectorAll('.wpuc-select-toggle');
  var startScanButton = document.getElementById('wpuc-start-scan');
  var deleteButton = document.getElementById('wpuc-delete-selected');
  var deleteAllButton = document.getElementById('wpuc-delete-all-filtered');
  var progressWrap = document.getElementById('wpuc-progress');
  var progressFill = document.getElementById('wpuc-progress-fill');
  var progressLabel = document.getElementById('wpuc-progress-label');
  var progressCount = document.getElementById('wpuc-progress-count');
  var notice = document.getElementById('wpuc-inline-notice');
	
  document.getElementById('debugToggle').onclick = function() {
	document.getElementById('debugPanel').classList.toggle('active');
  }

  function toggleGroup(targetName, forceChecked) {
    var boxes = document.querySelectorAll('input[name="' + targetName + '"]');
    boxes.forEach(function (box) {
      box.checked = typeof forceChecked === 'boolean' ? forceChecked : !box.checked;
    });
  }

  function showNotice(message, type) {
    if (!notice) return;
    notice.hidden = false;
    notice.className = 'wpuc-inline-notice ' + (type || 'info');
    notice.textContent = message;
  }

  function updateProgress(progress) {
    if (!progressWrap || !progressFill || !progressLabel || !progressCount || !progress) return;
    progressWrap.hidden = false;
    progressFill.style.width = (progress.percent || 0) + '%';
    progressLabel.textContent = progress.label || wpucAdmin.strings.working;
    var processed = progress.processed || 0;
    var total = progress.total || 0;
    progressCount.textContent = (progress.percent || 0) + '% • ' + processed + '/' + total;
  }

  function updateSummary(summary) {
    if (!summary) return;
    Object.keys(summary).forEach(function (key) {
      var node = document.querySelector('[data-summary="' + key + '"]');
      if (node) node.textContent = summary[key];
    });
  }

  function updateDebugPanel(debug) {
    if (!debug) return;
    var message = document.getElementById('wpuc-debug-message');
    var phase = document.getElementById('wpuc-debug-phase');
    var cursor = document.getElementById('wpuc-debug-cursor');
    var updated = document.getElementById('wpuc-debug-updated');
    var error = document.getElementById('wpuc-debug-error');
    var warnings = document.getElementById('wpuc-debug-warnings');
    var pill = document.querySelector('.wpuc-debug-panel .wpuc-pill');
    if (message) message.textContent = debug.message || '';
    if (phase) phase.textContent = debug.active_phase || debug.phase || '';
    if (cursor) cursor.textContent = (debug.active_cursor !== undefined ? debug.active_cursor : (debug.cursor || 0));
    if (updated) updated.textContent = debug.updated_at || '';
    if (error) error.textContent = debug.error || '';
    if (warnings) {
      warnings.innerHTML = '';
      var items = Array.isArray(debug.warnings) ? debug.warnings : [];
      if (!items.length) {
        var li = document.createElement('li');
        li.textContent = 'No warnings recorded yet.';
        warnings.appendChild(li);
      } else {
        items.forEach(function (warning) {
          var li = document.createElement('li');
          li.textContent = warning;
          warnings.appendChild(li);
        });
      }
    }
    if (pill && debug.status) {
      pill.textContent = debug.status.charAt(0).toUpperCase() + debug.status.slice(1);
      pill.className = 'wpuc-pill wpuc-pill-' + debug.status;
    }
  }

  function postAjax(action, payload) {
    var params = new URLSearchParams();
    params.append('action', action);
    params.append('nonce', wpucAdmin.nonce);
    Object.keys(payload || {}).forEach(function (key) {
      var value = payload[key];
      if (Array.isArray(value)) {
        value.forEach(function (item) {
          params.append(key + '[]', item);
        });
      } else {
        params.append(key, value);
      }
    });

    return fetch(wpucAdmin.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString()
    }).then(function (response) { return response.json(); });
  }

  function refreshPage() { window.location.reload(); }

  function processScanLoop() {
    postAjax('wpuc_process_scan', {}).then(function (result) {
      if (!result.success) {
        showNotice((result.data && result.data.message) || wpucAdmin.strings.scanFailed, 'error');
        updateDebugPanel(result.data && result.data.debug);
        startScanButton.disabled = false;
        return;
      }
      updateProgress(result.data.progress);
      updateDebugPanel(result.data.debug);
      if (result.data.done) {
        showNotice(wpucAdmin.strings.scanComplete, 'success');
        setTimeout(refreshPage, 500);
        return;
      }
      window.setTimeout(processScanLoop, 100);
    }).catch(function () {
      showNotice(wpucAdmin.strings.scanFailed, 'error');
      startScanButton.disabled = false;
    });
  }

  function runDeleteBatches() {
    var attachmentIds = Array.prototype.map.call(document.querySelectorAll('input[name="attachment_ids[]"]:checked'), function (node) { return node.value; });
    var filePaths = Array.prototype.map.call(document.querySelectorAll('input[name="file_paths[]"]:checked'), function (node) { return node.value; });
    if (!attachmentIds.length && !filePaths.length) {
      showNotice(wpucAdmin.strings.selectOne, 'warning');
      return;
    }
    deleteButton.disabled = true;
    if (deleteAllButton) deleteAllButton.disabled = true;
    progressWrap.hidden = false;
    var deletedAttachments = 0;
    var deletedFiles = 0;

    function nextDeleteBatch() {
      var batchAttachments = attachmentIds.splice(0, wpucAdmin.batchSize);
      var batchFiles = filePaths.splice(0, wpucAdmin.batchSize);
      var totalRemaining = attachmentIds.length + filePaths.length + batchAttachments.length + batchFiles.length;
      var totalDone = deletedAttachments + deletedFiles;
      var totalAll = totalRemaining + totalDone;
      var percent = totalAll ? Math.round((totalDone / totalAll) * 100) : 0;
      updateProgress({ label: wpucAdmin.strings.deletePhase, processed: totalDone, total: totalAll, percent: percent });
      postAjax('wpuc_delete_batch', { attachment_ids: batchAttachments, file_paths: batchFiles }).then(function (result) {
        if (!result.success) {
          showNotice((result.data && result.data.message) || wpucAdmin.strings.deleteFailed, 'error');
          deleteButton.disabled = false;
          if (deleteAllButton) deleteAllButton.disabled = false;
          return;
        }
        deletedAttachments += (result.data.deleted && result.data.deleted.attachments) || 0;
        deletedFiles += (result.data.deleted && result.data.deleted.files) || 0;
        updateSummary(result.data.summary);
        if (attachmentIds.length || filePaths.length) {
          window.setTimeout(nextDeleteBatch, 80);
          return;
        }
        updateProgress({ label: wpucAdmin.strings.deleteDone, processed: deletedAttachments + deletedFiles, total: deletedAttachments + deletedFiles, percent: 100 });
        showNotice(wpucAdmin.strings.deleteComplete + ' ' + deletedAttachments + ' media items and ' + deletedFiles + ' loose files removed.', 'success');
        setTimeout(refreshPage, 500);
      }).catch(function () {
        showNotice(wpucAdmin.strings.deleteFailed, 'error');
        deleteButton.disabled = false;
        if (deleteAllButton) deleteAllButton.disabled = false;
      });
    }

    nextDeleteBatch();
  }

  function runDeleteAllFiltered() {
    if (!deleteAllButton) return;
    deleteAllButton.disabled = true;
    if (deleteButton) deleteButton.disabled = true;
    progressWrap.hidden = false;
    var deletedAttachments = 0;
    var deletedFiles = 0;
    var totalEstimate = null;

    function nextBatch() {
      postAjax('wpuc_delete_all_filtered_batch', Object.assign({}, wpucAdmin.filters, { batch_size: wpucAdmin.batchSize })).then(function (result) {
        if (!result.success) {
          showNotice((result.data && result.data.message) || wpucAdmin.strings.deleteFailed, 'error');
          deleteAllButton.disabled = false;
          if (deleteButton) deleteButton.disabled = false;
          return;
        }
        var deletedNowA = (result.data.deleted && result.data.deleted.attachments) || 0;
        var deletedNowF = (result.data.deleted && result.data.deleted.files) || 0;
        deletedAttachments += deletedNowA;
        deletedFiles += deletedNowF;
        var remaining = (result.data.remaining && result.data.remaining.total) || 0;
        if (totalEstimate === null) totalEstimate = remaining + deletedAttachments + deletedFiles;
        var processed = deletedAttachments + deletedFiles;
        var percent = totalEstimate ? Math.round((processed / totalEstimate) * 100) : 100;
        updateProgress({ label: wpucAdmin.strings.deleteAllPhase, processed: processed, total: totalEstimate || processed, percent: Math.min(percent, 100) });
        updateSummary(result.data.summary);
        if (remaining > 0) {
          window.setTimeout(nextBatch, 80);
          return;
        }
        updateProgress({ label: wpucAdmin.strings.deleteDone, processed: processed, total: processed, percent: 100 });
        showNotice(wpucAdmin.strings.deleteComplete + ' ' + deletedAttachments + ' media items and ' + deletedFiles + ' loose files removed.', 'success');
        setTimeout(refreshPage, 500);
      }).catch(function () {
        showNotice(wpucAdmin.strings.deleteFailed, 'error');
        deleteAllButton.disabled = false;
        if (deleteButton) deleteButton.disabled = false;
      });
    }

    nextBatch();
  }

  masterToggles.forEach(function (toggle) {
    toggle.addEventListener('change', function () { toggleGroup(toggle.getAttribute('data-target'), toggle.checked); });
  });
  quickToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () { toggleGroup(toggle.getAttribute('data-target')); });
  });
  if (startScanButton) {
    startScanButton.addEventListener('click', function () {
      startScanButton.disabled = true;
      showNotice(wpucAdmin.strings.working, 'info');
      postAjax('wpuc_start_scan', {}).then(function (result) {
        if (!result.success) {
          showNotice((result.data && result.data.message) || wpucAdmin.strings.scanFailed, 'error');
          updateDebugPanel(result.data && result.data.debug);
          startScanButton.disabled = false;
          return;
        }
        updateProgress(result.data.progress);
        updateDebugPanel(result.data.debug);
        processScanLoop();
      }).catch(function () {
        showNotice(wpucAdmin.strings.scanFailed, 'error');
        startScanButton.disabled = false;
      });
    });
  }
  if (deleteButton) deleteButton.addEventListener('click', runDeleteBatches);
  if (deleteAllButton) deleteAllButton.addEventListener('click', runDeleteAllFiltered);
});
