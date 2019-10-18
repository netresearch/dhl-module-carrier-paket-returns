/**
 * See LICENSE.md for license details.
 */
define([
    'pdfjs-dist/build/pdf'
], function (pdfjs) {
    'use strict';

    return function (config, element) {
        var pdfData = atob(config.data);

        var loadingTask = pdfjs.getDocument({
            data: pdfData
        });

        loadingTask.promise.then(function (pdf) {
            // Fetch the first page
            var pageNumber = 1;

            pdf.getPage(pageNumber).then(function (page) {
                // Prepare canvas using PDF page dimensions
                var viewport = page.getViewport({scale: 1,});

                var scale = element.parentElement.clientWidth / viewport.width;
                var scaledViewport = page.getViewport({scale: scale,});

                element.height = scaledViewport.height;
                element.width = scaledViewport.width;

                // Render PDF page into canvas context
                var renderContext = {
                    canvasContext: element.getContext('2d'),
                    viewport: scaledViewport
                };

                page.render(renderContext);
            });
        }, function (reason) {
            // PDF loading error
            console.error(reason);
        });
    };
});
