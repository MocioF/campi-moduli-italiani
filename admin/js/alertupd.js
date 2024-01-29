"use strict";
jQuery(document).ready(function ($) {
  var toplevelmenu = $("#toplevel_page_gcmi .wp-menu-name");
  $.ajax({
    data: {
      action: "gcmi_show_data_need_update_notice",
      _ajax_nonce: gcmi_menu_admin.nonce
    },
    dataType: "json",
    error: function (res) {
      console.log(res);
    },
    success: function (res) {
      setNotice(res, toplevelmenu);
    },
    type: "post",
    url: gcmi_menu_admin.ajax_url
  });

  function setNotice(res, jqobj) {
    if (res.data.num !== 0) {
      jqobj.append(" <span class=\"update-plugins " + res.data.num + "\">" +
        "<span class=\"plugin-count\">" + res.data.formatted + "</span></span>");
    }
  }
});