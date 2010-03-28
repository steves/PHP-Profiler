(function($) {
    var PQP_DETAILS = false;
    var PQP_HEIGHT = 'short';
    var PROFILER_DETAILS = false;

    function hideAllTabs() {
        $('#profiler').removeClass('console')
                 .removeClass('speed')
                 .removeClass('queries')
                 .removeClass('memory')
                 .removeClass('files');
    }

    $(document).ready(function() {
    setTimeout(function() { $('#profiler-container').css('display', 'block') }, 10);

        $('.query-profile H4').css('cursor', 'pointer').click(function() {
            if ($('table', $(this).parent()).is(':hidden')) {
                $(this).html('&#187; Hide Query Profile');
                $('table', $(this).parent()).css('display', 'block');
            } else {
                $(this).html('&#187; Show Query Profile');
                $('table', $(this).parent()).css('display', 'none');
            }
        });

        $('.detailsToggle').click(function() {
            if (PQP_DETAILS) {
                $('#profiler-container').addClass('hideDetails');
                PQP_DETAILS = false;
            } else {
            $('#profiler-container').removeClass('hideDetails');
                PQP_DETAILS = true;
            }
            return false;
        });

        $('.heightToggle').click(function() {
            var container = $('#pqp-container');

            if (container.hasClass('tallDetails'))
                container.removeClass('tallDetails');
            else
                container.addClass('tallDetails');
        });

        $('.tab').click(function() {
            hideAllTabs();
            $('#profiler').addClass($(this).attr('id'));
        });
    });
})(jQuery);