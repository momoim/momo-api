pm.collections = {
    areLoaded:false,
    items:[],

    init:function () {
        this.addCollectionListeners();
    },

    addCollectionListeners:function () {
        var $collection_items = $('#collection-items');
        $collection_items.on("mouseenter", ".sidebar-collection .sidebar-collection-head", function () {
            var actionsEl = jQuery('.collection-head-actions', this);
            actionsEl.css('display', 'block');
        });

        $collection_items.on("mouseleave", ".sidebar-collection .sidebar-collection-head", function () {
            var actionsEl = jQuery('.collection-head-actions', this);
            actionsEl.css('display', 'none');
        });

        $collection_items.on("click", ".sidebar-collection-head-name", function () {
            var id = $(this).attr('data-id');
            pm.collections.toggleRequestList(id);
        });

        $collection_items.on("click", ".collection-head-actions .label", function () {
            var id = $(this).parent().parent().parent().attr('data-id');
            pm.collections.toggleRequestList(id);
        });

        $collection_items.on("click", ".request-actions-delete", function () {
            var id = $(this).attr('data-id');
            pm.collections.deleteCollectionRequest(id);
        });

        $collection_items.on("click", ".request-actions-load", function () {
            var id = $(this).attr('data-id');
            pm.collections.getCollectionRequest(id);
        });

        $collection_items.on("click", ".request-actions-edit", function () {
            var id = $(this).attr('data-id');
            $('#form-edit-collection-request .collection-request-id').val(id);

            pm.indexedDB.getCollectionRequest(id, function (req) {
                $('#form-edit-collection-request .collection-request-name').val(req.name);
                $('#form-edit-collection-request .collection-request-description').val(req.description);
                $('#modal-edit-collection-request').modal('show');
            });
        });

        $collection_items.on("click", ".collection-actions-edit", function () {
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');
            $('#form-edit-collection .collection-id').val(id);
            $('#form-edit-collection .collection-name').val(name);
            $('#modal-edit-collection').modal('show');
        });

        $collection_items.on("click", ".collection-actions-delete", function () {
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');

            $('#modal-delete-collection-yes').attr('data-id', id);
            $('#modal-delete-collection-name').html(name);
        });

        $('#modal-delete-collection-yes').on("click", function () {
            var id = $(this).attr('data-id');
            pm.collections.deleteCollection(id);
        });

        $('#import-collection-url-submit').on("click", function () {
            var url = $('#import-collection-url-input').val();
            pm.collections.importCollectionFromUrl(url);
        });

        $collection_items.on("click", ".collection-actions-download", function () {
            var id = $(this).attr('data-id');
            $("#modal-share-collection").modal("show");
            $('#share-collection-get-link').attr("data-collection-id", id);
            $('#share-collection-download').attr("data-collection-id", id);
            $('#share-collection-link').css("display", "none");
        });

        $('#share-collection-get-link').on("click", function () {
            var id = $(this).attr('data-collection-id');
            pm.collections.uploadCollection(id, function (link) {
                $('#share-collection-link').css("display", "block");
                $('#share-collection-link').html(link);
            });
        });

        $('#share-collection-download').on("click", function () {
            var id = $(this).attr('data-collection-id');
            pm.collections.saveCollection(id);
        });

        $('#request-samples').on("click", ".sample-response-name", function () {
            var id = $(this).attr("data-id");
            pm.collections.loadResponseInEditor(id);
        });

        $('#request-samples').on("click", ".sample-response-delete", function () {
            var id = $(this).attr("data-id");
            pm.collections.removeSampleResponse(id);
        });

        var dropZone = document.getElementById('import-collection-dropzone');
        dropZone.addEventListener('dragover', function (evt) {
            evt.stopPropagation();
            evt.preventDefault();
            evt.dataTransfer.dropEffect = 'copy'; // Explicitly show this is a copy.
        }, false);

        dropZone.addEventListener('drop', function (evt) {
            evt.stopPropagation();
            evt.preventDefault();
            var files = evt.dataTransfer.files; // FileList object.

            pm.collections.importCollections(files);
        }, false);

        $('#collection-files-input').on('change', function (event) {
            var files = event.target.files;
            pm.collections.importCollections(files);
            $('#collection-files-input').val("");
        });
    },

    getCollectionData:function (id, callback) {
        pm.indexedDB.getCollection(id, function (data) {
            var collection = data;
            pm.indexedDB.getAllRequestsInCollection(collection, function (collection, data) {
                var ids = [];
                for (var i = 0, count = data.length; i < count; i++) {
                    ids.push(data[i].id);
                }

                //Get all collection requests with one call
                collection['requests'] = data;
                var name = collection['name'] + ".json";
                var type = "application/json";
                var filedata = JSON.stringify(collection);
                callback(name, type, filedata);
            });
        });
    },

    saveCollection:function (id) {
        pm.collections.getCollectionData(id, function (name, type, filedata) {
            pm.filesystem.saveAndOpenFile(name, filedata, type, function () {
            });
        });
    },

    uploadCollection:function (id, callback) {
        pm.collections.getCollectionData(id, function (name, type, filedata) {
            var uploadUrl = pm.webUrl + '/collections';
            $.ajax({
                type:'POST',
                url:uploadUrl,
                data:filedata,
                success:function (data) {
                    var link = data.link;
                    callback(link);
                }
            });

        });
    },

    importCollectionData:function (collection) {
        pm.indexedDB.addCollection(collection, function (c) {
            var message = {
                name:collection.name,
                action:"added"
            };

            $('.modal-import-alerts').append(Handlebars.templates.message_collection_added(message));

            var requests = [];

            var ordered = false;
            if ("order" in collection) {
                ordered = true;
            }

            for (var i = 0; i < collection.requests.length; i++) {
                var request = collection.requests[i];
                request.collectionId = collection.id;
                var newId = guid();

                if (ordered) {
                    var currentId = request.id;
                    var loc = _.indexOf(collection["order"], currentId);
                    collection["order"][loc] = newId;
                }

                request.id = newId;

                if ("responses" in request) {
                    var j, count;
                    for (j = 0, count = request["responses"].length; j < count; j++) {
                        request["responses"][j].id = guid();
                        request["responses"][j].collectionRequestId = newId;                        
                    }
                }

                pm.indexedDB.addCollectionRequest(request, function (req) {});
                requests.push(request);
            }

            collection.requests = requests;

            pm.collections.render(collection);
        });
    },

    importCollections:function (files) {
        // Loop through the FileList
        for (var i = 0, f; f = files[i]; i++) {
            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function (theFile) {
                return function (e) {
                    // Render thumbnail.
                    var data = e.currentTarget.result;
                    var collection = JSON.parse(data);
                    collection.id = guid();
                    pm.collections.importCollectionData(collection);
                };
            })(f);

            // Read in the image file as a data URL.
            reader.readAsText(f);
        }
    },

    importCollectionFromUrl:function (url) {
        $.get(url, function (data) {
            var collection = data;
            collection.id = guid();
            pm.collections.importCollectionData(collection);
        });
    },

    getCollectionRequest:function (id) {
        pm.indexedDB.getCollectionRequest(id, function (request) {
            pm.request.isFromCollection = true;
            pm.request.collectionRequestId = id;
            pm.request.loadRequestInEditor(request, true);
        });
    },

    loadResponseInEditor:function (id) {
        var responses = pm.request.responses;        
        var responseIndex = find(responses, function (item, i, responses) {
            return item.id === id;
        });

        var response = responses[responseIndex];
        pm.request.loadRequestInEditor(response.request, false, true);
        pm.request.response.render(response);
    },

    removeSampleResponse:function (id) {
        var responses = pm.request.responses;
        var responseIndex = find(responses, function (item, i, responses) {
            return item.id === id;
        });

        var response = responses[responseIndex];
        responses.splice(responseIndex, 1);

        pm.indexedDB.getCollectionRequest(response.collectionRequestId, function (request) {
            request["responses"] = responses;
            pm.indexedDB.updateCollectionRequest(request, function () {
                $('#request-samples table tr[data-id="' + response.id + '"]').remove();
            });

        });
    },

    openCollection:function (id) {
        var target = "#collection-requests-" + id;
        if ($(target).css("display") === "none") {
            $(target).slideDown(100, function () {
                pm.layout.refreshScrollPanes();
            });
        }
    },

    toggleRequestList:function (id) {
        var target = "#collection-requests-" + id;
        var label = "#collection-" + id + " .collection-head-actions .label";
        if ($(target).css("display") === "none") {
            $(target).slideDown(100, function () {
                pm.layout.refreshScrollPanes();
            });
        }
        else {
            $(target).slideUp(100, function () {
                pm.layout.refreshScrollPanes();
            });
        }
    },

    addCollection:function () {
        var newCollection = $('#new-collection-blank').val();

        var collection = new Collection();

        if (newCollection) {
            //Add the new collection and get guid
            collection.id = guid();
            collection.name = newCollection;
            pm.indexedDB.addCollection(collection, function (collection) {
                pm.collections.render(collection);
            });

            $('#new-collection-blank').val("");
        }

        $('#modal-new-collection').modal('hide');
    },

    updateCollectionFromCurrentRequest:function () {
        var url = $('#url').val();
        var collectionRequest = new CollectionRequest();
        collectionRequest.id = pm.request.collectionRequestId;
        collectionRequest.headers = pm.request.getPackedHeaders();
        collectionRequest.url = url;
        collectionRequest.method = pm.request.method;
        collectionRequest.data = pm.request.body.getData();
        collectionRequest.dataMode = pm.request.dataMode;
        collectionRequest.time = new Date().getTime();

        pm.indexedDB.getCollectionRequest(collectionRequest.id, function (req) {
            collectionRequest.name = req.name;
            collectionRequest.description = req.description;
            collectionRequest.collectionId = req.collectionId;
            $('#sidebar-request-' + req.id + " .request .label").removeClass('label-method-' + req.method);

            pm.indexedDB.updateCollectionRequest(collectionRequest, function (request) {
                var requestName;
                if (request.name == undefined) {
                    request.name = request.url;
                }

                requestName = limitStringLineWidth(request.name, 43);

                $('#sidebar-request-' + request.id + " .request .request-name").html(requestName);
                $('#sidebar-request-' + request.id + " .request .label").html(request.method);
                $('#sidebar-request-' + request.id + " .request .label").addClass('label-method-' + request.method);
                noty(
                    {
                        type:'success',
                        text:'Saved request',
                        layout:'topRight',
                        timeout:750
                    });
            });
        });

    },

    addRequestToCollection:function () {
        var existingCollectionId = $('#select-collection').val();
        var newCollection = $("#new-collection").val();
        var newRequestName = $('#new-request-name').val();
        var newRequestDescription = $('#new-request-description').val();

        var url = $('#url').val();
        if (newRequestName === "") {
            newRequestName = url;
        }

        var collection = new Collection();

        var collectionRequest = new CollectionRequest();
        collectionRequest.id = guid();
        collectionRequest.headers = pm.request.getPackedHeaders();
        collectionRequest.url = url;
        collectionRequest.method = pm.request.method;
        collectionRequest.data = pm.request.body.getData();
        collectionRequest.dataMode = pm.request.dataMode;
        collectionRequest.name = newRequestName;
        collectionRequest.description = newRequestDescription;
        collectionRequest.time = new Date().getTime();
        collectionRequest.responses = pm.request.responses;

        if (newCollection) {
            //Add the new collection and get guid
            collection.id = guid();
            collection.name = newCollection;
            pm.indexedDB.addCollection(collection, function (collection) {
                $('#sidebar-section-collections .empty-message').css("display", "none");
                $('#new-collection').val("");
                collectionRequest.collectionId = collection.id;

                $('#select-collection').append(Handlebars.templates.item_collection_selector_list(collection));
                $('#collection-items').append(Handlebars.templates.item_collection_sidebar_head(collection));

                $('a[rel="tooltip"]').tooltip();
                pm.layout.refreshScrollPanes();
                pm.indexedDB.addCollectionRequest(collectionRequest, function (req) {
                    var targetElement = "#collection-requests-" + req.collectionId;
                    pm.urlCache.addUrl(req.url);

                    if (typeof req.name === "undefined") {
                        req.name = req.url;
                    }
                    req.name = limitStringLineWidth(req.name, 43);

                    $(targetElement).append(Handlebars.templates.item_collection_sidebar_request(req));

                    pm.layout.refreshScrollPanes();

                    pm.request.isFromCollection = true;
                    pm.request.collectionRequestId = collectionRequest.id;
                    $('#update-request-in-collection').css("display", "inline-block");
                    pm.collections.openCollection(collectionRequest.collectionId);
                });
            });
        }
        else {
            //Get guid of existing collection
            collection.id = existingCollectionId;
            collectionRequest.collectionId = collection.id;
            pm.indexedDB.addCollectionRequest(collectionRequest, function (req) {
                var targetElement = "#collection-requests-" + req.collectionId;
                pm.urlCache.addUrl(req.url);

                if (typeof req.name === "undefined") {
                    req.name = req.url;
                }
                req.name = limitStringLineWidth(req.name, 43);

                $(targetElement).append(Handlebars.templates.item_collection_sidebar_request(req));
                pm.layout.refreshScrollPanes();

                pm.request.isFromCollection = true;
                pm.request.collectionRequestId = collectionRequest.id;
                $('#update-request-in-collection').css("display", "inline-block");
                pm.collections.openCollection(collectionRequest.collectionId);
            });
        }

        pm.layout.sidebar.select("collections");

        $('#request-meta').css("display", "block");
        $('#request-name').css("display", "block");
        $('#request-description').css("display", "block");
        $('#request-name').html(newRequestName);
        $('#request-description').html(newRequestDescription);
        $('#sidebar-selectors a[data-id="collections"]').tab('show');
    },

    getAllCollections:function () {
        $('#collection-items').html("");
        $('#select-collection').html("<option>Select</option>");
        pm.indexedDB.getCollections(function (items) {
            pm.collections.items = items;
            pm.collections.items.sort(sortAlphabetical);

            var itemsLength = items.length;

            if (itemsLength === 0) {
                $('#sidebar-section-collections').append(Handlebars.templates.message_no_collection({}));
            }
            else {
                for (var i = 0; i < itemsLength; i++) {
                    var collection = items[i];
                    pm.indexedDB.getAllRequestsInCollection(collection, function (collection, requests) {
                        collection.requests = requests;
                        pm.collections.render(collection);
                    });
                }
            }


            pm.collections.areLoaded = true;
            pm.layout.refreshScrollPanes();
        });
    },

    render:function (collection) {
        $('#sidebar-section-collections .empty-message').css("display", "none");

        var currentEl = $('#collection-' + collection.id);
        if (currentEl) {
            currentEl.remove();
        }

        $('#select-collection').append(Handlebars.templates.item_collection_selector_list(collection));
        $('#collection-items').append(Handlebars.templates.item_collection_sidebar_head(collection));

        $('a[rel="tooltip"]').tooltip();

        if ("requests" in collection) {
            var id = collection.id;
            var requests = collection.requests;
            var targetElement = "#collection-requests-" + id;
            var count = requests.length;

            if (count > 0) {
                for (var i = 0; i < count; i++) {
                    pm.urlCache.addUrl(requests[i].url);
                    if (typeof requests[i].name === "undefined") {
                        requests[i].name = requests[i].url;
                    }
                    requests[i].name = limitStringLineWidth(requests[i].name, 40);
                }

                //Sort requests as A-Z order
                if (!("order" in collection)) {
                    requests.sort(sortAlphabetical);
                }
                else {
                    if(collection["order"].length == requests.length) {
                        var orderedRequests = [];                    
                        for (var j = 0, len = collection["order"].length; j < len; j++) {
                            var element = _.find(requests, function (request) {
                                return request.id == collection["order"][j]
                            });
                            orderedRequests.push(element);
                        }
                        requests = orderedRequests;
                    }
                }

                $(targetElement).append(Handlebars.templates.collection_sidebar({"items":requests}));
                $(targetElement).sortable({
                    update:function (event, ui) {
                        var target_parent = $(event.target).parents(".sidebar-collection-requests");                        
                        var target_parent_collection = $(event.target).parents(".sidebar-collection");                        
                        var collection_id = $(target_parent_collection).attr("data-id");
                        var ul_id = $(target_parent.context).attr("id");                        
                        var collection_requests = $(target_parent.context).children("li");
                        var count = collection_requests.length;
                        var order = [];

                        for (var i = 0; i < count; i++) {
                            var li_id = $(collection_requests[i]).attr("id");
                            var request_id = $("#" + li_id + " .request").attr("data-id");
                            order.push(request_id);
                        }

                        pm.indexedDB.getCollection(collection_id, function (collection) {                            
                            collection["order"] = order;
                            pm.indexedDB.updateCollection(collection, function (collection) {
                            });
                        });

                    }
                });
            }

        }

        pm.layout.refreshScrollPanes();
    },

    deleteCollectionRequest:function (id) {
        pm.indexedDB.deleteCollectionRequest(id, function () {
            pm.layout.sidebar.removeRequestFromHistory(id);
        });
    },

    deleteCollection:function (id) {
        pm.indexedDB.deleteCollection(id, function () {
            pm.layout.sidebar.removeCollection(id);

            var target = '#select-collection option[value="' + id + '"]';
            $(target).remove();
        });
    },

    saveResponseAsSample:function (response) {
        pm.indexedDB.getCollectionRequest(response.collectionRequestId, function (request) {
            if ("responses" in request && request["responses"] !== undefined) {
                request["responses"].push(response);
            }
            else {
                request["responses"] = [response];
            }

            pm.request.responses = request["responses"];
            pm.indexedDB.updateCollectionRequest(request, function () {
                noty(
                    {
                        type:'success',
                        text:'Saved response',
                        layout:'topRight',
                        timeout:750
                    });

                $('#request-samples').css("display", "block");
                $('#request-samples table').append(Handlebars.templates.item_sample_response(response));
            });

        });
    }
};