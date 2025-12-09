define(['jquery'], function($) {

    const SELECTOR = '.secondary-navigation .nav-link.active.active_tree_node';
    const LABEL_SOURCE_SELECTOR = '.bbbext-bnx-navlabel';

    /**
     * Determine the label to use for the secondary navigation node.
     *
     * @param {string} provided Optional label supplied by the caller.
     * @returns {string|undefined}
     */
    const resolveLabel = function(provided) {
        if (typeof provided === 'string' && provided.trim() !== '') {
            return provided;
        }

        const source = $(LABEL_SOURCE_SELECTOR).first();
        const label = source.data('label');

        if (typeof label === 'string' && label.trim() !== '') {
            return label;
        }

        return undefined;
    };

    return {
        init: function(label) {
            // Wait until DOM is ready.
            $(function() {
                const node = $(SELECTOR);

                if (!node.length) {
                    return;
                }

                const resolvedLabel = resolveLabel(label);
                if (!resolvedLabel) {
                    return;
                }

                node.text(resolvedLabel);
            });
        }
    };
});
