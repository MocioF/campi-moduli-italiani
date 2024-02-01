/*global
 console, gcmi_menu_admin, jQuery
 */
jQuery(document).ready(function ($) {
  "use strict";
  var toplevelmenu = $("#toplevel_page_gcmi .wp-menu-name");
  $.ajax({
    data: {
      _ajax_nonce: gcmi_menu_admin.nonce,
      action: "gcmi_show_data_need_update_notice"
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
        "<span class=\"plugin-count\">" + res.data.formatted +
        "</span></span>");
    }
  }
});