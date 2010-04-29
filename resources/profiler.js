(function($) {
    var profiler_details = false;
    var height_toggle = false;
    var selected_log_type = null;

    function hideAllTabs() {
        $('#profiler').removeClass('console')
                 .removeClass('speed')
                 .removeClass('queries')
                 .removeClass('memory')
                 .removeClass('files');
    }

    $(document).ready(function() {
        setTimeout(function() {
            $('#profiler-container').css('display', 'block');
        }, 10);

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
            if (profiler_details) {
                $('#profiler-container').addClass('hideDetails');
                profiler_details = false;
            } else {
                $('#profiler-container').removeClass('hideDetails');
                profiler_details = true;
            }

            return false;
        });

        $('.heightToggle').click(function() {
            height_toggle = !height_toggle;

            $('.profiler-box').each(function() {
                $(this).css('height', (height_toggle ? '500px' : '200px'));
            });
        });

        $('.tab').css('cursor', 'pointer').click(function() {
            hideAllTabs();

            $(this).addClass('active');
            $('#profiler').addClass($(this).attr('id'));

            if (!profiler_details) {
                profiler_details = true;
                $('#profiler-container').removeClass('hideDetails');
            }
        });

        $('#profiler-console .side td').each(function() {
            var log_type = $(this).attr('id').split('-')[1];
            var log_count = $('var', $(this)).html();

            if (log_count == 0) {
                return;
            }

            $(this).css('cursor', 'pointer').click(function() {
                $('#profiler-console .main tr').each(function() {
                    var row_type = $(this).attr('class').split('-')[1];

                    if (row_type == log_type || selected_log_type == log_type) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                $('#profiler-console .side td').removeClass('selected');

                if (selected_log_type == log_type) {
                    selected_log_type = null;
                } else {
                    selected_log_type = log_type;
                    $(this).addClass('selected');
                }
            });
        });
    });
})(jQuery);