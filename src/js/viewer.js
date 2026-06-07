var intervalId = setInterval(function() {
    refreshViewer()
}, 10 * 1000)

function refreshViewer() {
    var showError = function () {
        $(".viewer-container").hide()
        $(".viewer-error").show()
    }

    var showData = function () {
        $(".viewer-container").show()
        $(".viewer-error").hide()
    }

    $.ajax({
        url: "api/getviewerhtml.php",
        success: function (result) {
            $(".viewer-container").html(result)
            updateHooks()
            showData()
        },
        error: function(result) {
            showError()
        },
        complete: function () {
            $(".viewer-container").addClass("loaded")
        }
    })
}

// Re-binds all interactive viewer handlers after the container HTML is replaced.
// Called once on page load (DOMContentLoaded) and after every AJAX refresh.
function updateHooks() {
    var container = $(".viewer-container")

    // show-empty-channels toggle
    container.find("[data-emptychannels]").off("click").on("click", function (e) {
        var el = $(this)
        var show = el.data("emptychannels") === "show"
        container.find("[data-emptychannels]").show()
        el.hide()
        var emptyChannels = container.find(".not-occupied")
        show ? emptyChannels.show() : emptyChannels.hide()
    })

    // ENTER key on focused channel
    container.find("[data-channelid]").off("keypress").on("keypress", function (e) {
        if (e.which === 13) {
            $(this).click()
        }
    })

    // Click to connect to channel
    container.find("[data-channelid]").off("click").on("click", function (e) {
        if ($(this).parent(".channel-container").hasClass("is-spacer")) {
            return
        }
        if (!confirm(VIEWER_LANG.connection_alert)) {
            return
        }
        var cid = $(this).data("channelid")
        window.location = "ts3server://" + TS3_DISPLAY_IP + "/?cid=" + cid
    })

    // Hover / focus for client popovers
    container.find(".client-container").off("mouseenter mouseleave focusin focusout")
        .hover(function () {
            showPopover($(this).find(".client-name"))
        }, function () {
            $(this).find(".client-name").popover("hide")
        })
        .on("focusin focusout", function (e) {
            if (e.type === "focusin") {
                showPopover($(this).find(".client-name"))
            } else {
                $(this).find(".client-name").popover("hide")
            }
        })

    // Initialise popovers on client names
    container.find(".client-container .client-name").popover({
        title: VIEWER_LANG.client_info,
        content: function () {
            return '<div class="status-loader position-relative p-3"><div class="loader"></div></div>'
        },
        html: true,
        template: '<div class="popover" role="tooltip"><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
        placement: "bottom",
        trigger: "manual"
    })
}

// show the viewer tip if no cookie present
if (!Cookies.get("tswebsite_viewertip_hide")) {
    var alert = $("#server-viewer-tip")
    alert.show()

    alert.find(".close").click(function (e) {
        e.preventDefault()
        Cookies.set("tswebsite_viewertip_hide", true, {expires: 365});
    })
}

// Popover helper
function showPopover(el) {
    el.popover("show")

    var cldbid = el.parent().data("clientdbid")

    if (!cldbid) {
        return
    }

    var popoverDebounceMs = 250

    setTimeout(function () {
        var popoverId = el.attr("aria-describedby")

        if (!$("#" + popoverId).length) {
            return
        }

        $.ajax({
            url: "api/getclientinfo.php",
            data: { cldbid: cldbid },
            success: function (result) {
                if (!result.success) {
                    updatePopover(el.attr("aria-describedby"), "Result error", result.message || ":(")
                    return
                }

                var describeSeconds = function (seconds) {
                    return dayjs().subtract(seconds, 'second').fromNow()
                }

                var describeTimestamp = function (timestamp, skipSuffix) {
                    if (skipSuffix === undefined)
                        skipSuffix = true
                    return dayjs.unix(timestamp).fromNow(skipSuffix)
                }

                var data = result.data
                var title = escapeHtml(data.client_nickname)

                var idleSeconds = Math.round(data.client_idle_time / 1000)
                var onlineTimestamp = data.client_lastconnected
                var createdTimestamp = data.client_created

                var clientInfo = [
                    [VIEWER_LANG.last_active,  describeSeconds(idleSeconds)],
                    [VIEWER_LANG.online_time,   describeTimestamp(onlineTimestamp)],
                    [VIEWER_LANG.first_joined,  describeTimestamp(createdTimestamp, false)]
                ]

                var body = '<table>'
                clientInfo.forEach(function (entry) {
                    body += '<tr><td><b>' + entry[0] + '&nbsp;</b></td><td>' + entry[1] + '</td></tr>'
                })
                body += '</table>'

                updatePopover(popoverId, title, body)
            },
            error: function () {
                updatePopover(popoverId, "Ajax error", VIEWER_LANG.viewer_error)
            },
            complete: function () {
                el.popover("update")
            }
        })
    }, popoverDebounceMs)
}

function updatePopover(id, header, body) {
    if (!id) return
    var popover = $("#" + id)
    popover.find(".popover-header").html(header)
    popover.find(".popover-body").html(body)
}

// Initial hook binding after DOM is ready
$(function () {
    updateHooks()
})
