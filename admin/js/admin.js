jQuery(document).ready(function ($) {

    /*---------------------------------------------------------
      1. Helper Functions
    ---------------------------------------------------------*/
    function getFieldIcon(fieldType) {
        switch (fieldType) {
            case 'text_area': return '<i class="fas fa-align-left"></i>';
            case 'checkbox_group': return '<i class="fas fa-check-square"></i>';
            case 'radio_group': return '<i class="fas fa-dot-circle"></i>';
            case 'drop_down_list': return '<i class="fas fa-caret-down"></i>';
            case 'phone': return '<i class="fas fa-phone"></i>';
            case 'name': return '<i class="fas fa-user"></i>';
            case 'email': return '<i class="fas fa-envelope"></i>';
            case 'address': return '<i class="fas fa-map-marker-alt"></i>';
            case 'state': return '<i class="fas fa-star"></i>';
            case 'zip': return '<i class="fas fa-hashtag"></i>';
            case 'date': return '<i class="fas fa-calendar"></i>';
            default: return '<i class="fas fa-font"></i>';
        }
    }

    // Open the field editor modal.
    function openFieldEditor($field) {
    console.log("openFieldEditor called for field:", $field.data("field"));
    $("#field-options-modal").data("editField", $field);
    var currentTitle = $field.attr("data-title") || $field.find(".field-title").text();
    $("#field-label").val(currentTitle);
    var isRequired = $field.attr("data-required") === "true";
    $("#field-required").prop("checked", isRequired);
    var fieldType = $field.data("field");
    if (fieldType === "checkbox_group" || fieldType === "radio_group" || fieldType === "drop_down_list") {
        $("#options-container").show();
        var optionsVal = $field.attr("data-options");
        var currentOptions = optionsVal ? JSON.parse(optionsVal) : [];
        $("#field-options").val(currentOptions.join(", "));
        if (fieldType === "drop_down_list") {
            $("label[for='field-options-alignment']").hide();
            $("#field-options-alignment").hide();
        } else {
            $("label[for='field-options-alignment']").show();
            $("#field-options-alignment").show();
            var currentAlignment = $field.attr("data-options-alignment") || "vertical";
            $("#field-options-alignment").val(currentAlignment);
        }
    } else {
        $("#options-container").hide();
        $("#field-options").val("");
    }
    $("#field-options-modal").fadeIn();
}


    /*---------------------------------------------------------
      2. Form Data Functions
    ---------------------------------------------------------*/
    function loadSavedFormsList() {
        $("#form-selector, #delete-form-selector").empty();
        if (Object.keys(AminFormBuilder.saved_forms).length === 0) {
            $("#form-selector, #delete-form-selector").append(new Option("No forms available", "", true, true));
            return;
        }
        $.each(AminFormBuilder.saved_forms, function (formID) {
            $("#form-selector").append(new Option(formID, formID));
            $("#delete-form-selector").append(new Option(formID, formID));
        });
        $("#form-selector").val(Object.keys(AminFormBuilder.saved_forms)[0]);
    }

    function loadSavedForm(formID) {
    console.log("Loading form:", formID);
    $(".form-row").remove();
    if (AminFormBuilder.saved_forms && AminFormBuilder.saved_forms[formID]) {
        var savedFields = AminFormBuilder.saved_forms[formID];
        savedFields.forEach(function (field) {
            var fieldLabel = field.title ? field.title : field.field.charAt(0).toUpperCase() + field.field.slice(1);
            var columnNumber = field.column;
            var rowNumber = field.row || 1;
            ensureRowExists(rowNumber);
            // Explicitly check if field.options is defined; if so, output its JSON (even if empty)
            var optionsData = (typeof field.options !== 'undefined') ? JSON.stringify(field.options) : "";
            var newField = $("<div class='dropped-field' data-field='" + field.field + "' data-column='" + columnNumber + "' data-row='" + rowNumber + "' data-options='" + optionsData + "' data-title='" + (field.title ? field.title : "") + "'" +
                (field.optionsAlignment ? " data-options-alignment='" + field.optionsAlignment + "'" : "") +
                " data-required='" + (field.required ? "true" : "false") + "'>" +
                "<span class='field-icon'>" + getFieldIcon(field.field) + "</span> " +
                "<span class='field-title'>" + fieldLabel + "</span> " +
                "<span class='edit-field' style='cursor:pointer;'>✎</span> " +
                "<span class='remove-field' style='cursor:pointer;'>X</span></div>");
            $("#row-" + rowNumber + " #column-" + rowNumber + "-" + columnNumber + " .droppable-column").append(newField);
        });
        activateRemoveField();
        activateEditFieldIcon();
        makeColumnsDroppable();
    } else {
        addNewRow(1);
    }
}

    function ensureRowExists(rowID) {
        if ($("#row-" + rowID).length === 0) {
            addNewRow(rowID);
        }
    }
function openFieldEditor($field) {
    console.log("openFieldEditor called for field:", $field.data("field"));
    $("#field-options-modal").data("editField", $field);
    var fieldType = $field.data("field");
    
    // For HTML fields, use a textarea instead of a text input.
    if (fieldType === "html") {
        // If the current field-label is not a textarea, replace it.
        if ($("#field-label").prop("tagName").toLowerCase() !== "textarea") {
            $("#field-label").replaceWith('<textarea id="field-label" style="width:100%; margin-bottom:10px;"></textarea>');
        }
    } else {
        // For non-html fields, ensure a text input is present.
        if ($("#field-label").prop("tagName").toLowerCase() === "textarea") {
            $("#field-label").replaceWith('<input type="text" id="field-label" style="width:100%; margin-bottom:10px;" />');
        }
    }
    
    // Set current field value.
    var currentTitle = $field.attr("data-title") || $field.find(".field-title").text();
    $("#field-label").val(currentTitle);
    
    var isRequired = $field.attr("data-required") === "true";
    $("#field-required").prop("checked", isRequired);
    
    if (fieldType === "checkbox_group" || fieldType === "radio_group" || fieldType === "drop_down_list") {
        $("#options-container").show();
        var optionsVal = $field.attr("data-options");
        var currentOptions = optionsVal ? JSON.parse(optionsVal) : [];
        $("#field-options").val(currentOptions.join(", "));
        if (fieldType === "drop_down_list") {
            $("label[for='field-options-alignment']").hide();
            $("#field-options-alignment").hide();
        } else {
            $("label[for='field-options-alignment']").show();
            $("#field-options-alignment").show();
            var currentAlignment = $field.attr("data-options-alignment") || "vertical";
            $("#field-options-alignment").val(currentAlignment);
        }
    } else {
        $("#options-container").hide();
        $("#field-options").val("");
    }
    
    $("#field-options-modal").fadeIn();
}
function openFieldEditor($field) {
    console.log("openFieldEditor called for field:", $field.data("field"));
    $("#field-options-modal").data("editField", $field);
    var fieldType = $field.data("field");
    
    // For HTML fields, use a textarea instead of a text input.
    if (fieldType === "html") {
        // If the current field-label is not a textarea, replace it.
        if ($("#field-label").prop("tagName").toLowerCase() !== "textarea") {
            $("#field-label").replaceWith('<textarea id="field-label" style="width:100%; margin-bottom:10px;"></textarea>');
        }
    } else {
        // For non-html fields, ensure a text input is present.
        if ($("#field-label").prop("tagName").toLowerCase() === "textarea") {
            $("#field-label").replaceWith('<input type="text" id="field-label" style="width:100%; margin-bottom:10px;" />');
        }
    }
    
    // Set current field value.
    var currentTitle = $field.attr("data-title") || $field.find(".field-title").text();
    $("#field-label").val(currentTitle);
    
    var isRequired = $field.attr("data-required") === "true";
    $("#field-required").prop("checked", isRequired);
    
    if (fieldType === "checkbox_group" || fieldType === "radio_group" || fieldType === "drop_down_list") {
        $("#options-container").show();
        var optionsVal = $field.attr("data-options");
        var currentOptions = optionsVal ? JSON.parse(optionsVal) : [];
        $("#field-options").val(currentOptions.join(", "));
        if (fieldType === "drop_down_list") {
            $("label[for='field-options-alignment']").hide();
            $("#field-options-alignment").hide();
        } else {
            $("label[for='field-options-alignment']").show();
            $("#field-options-alignment").show();
            var currentAlignment = $field.attr("data-options-alignment") || "vertical";
            $("#field-options-alignment").val(currentAlignment);
        }
    } else {
        $("#options-container").hide();
        $("#field-options").val("");
    }
    
    $("#field-options-modal").fadeIn();
}

    /*---------------------------------------------------------
      3. Row and Column Functions
    ---------------------------------------------------------*/
    function addNewRow(rowID) {
        var newRow = $("<div class='form-row' id='row-" + rowID + "' style='position:relative;'><h3>Row " + rowID + "</h3></div>");
        var deleteRowBtn = $("<span class='delete-row' style='color:red; cursor:pointer; position:absolute; top:5px; right:5px;'>X</span>");
        deleteRowBtn.on("click", function () {
            if (confirm("Are you sure you want to delete this row?")) {
                newRow.remove();
            }
        });
        newRow.prepend(deleteRowBtn);
        var columnsContainer = $("<div class='form-columns row-container'></div>");
        newRow.append(columnsContainer);
        for (var i = 1; i <= 3; i++) {
            var newColumn = $("<div class='form-column' id='column-" + rowID + "-" + i + "' data-column='" + i + "'>" +
                "<h4>Column " + i + "</h4><div class='droppable-column'></div></div>");
            columnsContainer.append(newColumn);
        }
        $("#amin-form-rows").append(newRow);
        makeColumnsDroppable();
    }

    $("#add-new-row").click(function () {
        var rowCount = $(".form-row").length + 1;
        addNewRow(rowCount);
    });

    /*---------------------------------------------------------
      4. Droppable Columns & Drag-and-Drop Setup
    ---------------------------------------------------------*/
    function makeColumnsDroppable() {
        $(".droppable-column").droppable({
            accept: ".field-item",
            drop: function (event, ui) {
                var fieldType = ui.draggable.data("field");
                var fieldLabel = ui.draggable.text();
                var columnNumber = $(this).closest(".form-column").data("column");
                var rowNumber = $(this).closest(".form-row").attr("id").split("-")[1];

                if ($(this).find(".dropped-field[data-field='" + fieldType + "']").length > 0) {
                    alert("❌ This field is already added to this column.");
                    return;
                }

                // For radio and checkbox groups, set a default alignment (vertical).
                var alignmentAttr = "";
                var alignmentClass = "";
                if (fieldType === "checkbox_group" || fieldType === "radio_group") {
                    alignmentAttr = " data-options-alignment='vertical'";
                    alignmentClass = " vertical";
                }

                // Create new field element.
                var newField = $("<div class='dropped-field" + alignmentClass + "' data-field='" + fieldType + "' data-column='" + columnNumber + "' data-row='" + rowNumber + "' data-title='" + fieldLabel + "' data-required='false'" + alignmentAttr + ">" +
                    "<span class='field-icon'>" + getFieldIcon(fieldType) + "</span> " +
                    "<span class='field-title'>" + fieldLabel + "</span> " +
                    "<span class='edit-field' style='cursor:pointer;'>✎</span> " +
                    "<span class='remove-field' style='cursor:pointer;'>X</span></div>");
                $(this).append(newField);
                activateRemoveField();
                // Immediately open the editor.
                openFieldEditor(newField);
            }
        }).sortable({
            connectWith: ".droppable-column",
            items: ".dropped-field",
            update: function () {
                console.log("Fields reordered");
            }
        });
    }

    /*---------------------------------------------------------
      5. Draggable Field Items Setup
    ---------------------------------------------------------*/
    $(".field-item").draggable({
        helper: "clone",
        appendTo: "body",
        zIndex: 100,
        revert: "invalid",
        cancel: "",
        start: function (event, ui) {
            ui.helper.css({
                "opacity": "0.9",
                "border": "1px solid #ccc",
                "background": "#fff",
                "padding": "10px"
            });
        }
    });

    /*---------------------------------------------------------
      6. Field Editing Modal Setup
    ---------------------------------------------------------*/
    function activateEditFieldIcon() {
        $(document).on("click", ".edit-field", function (e) {
            e.stopPropagation();
            var $field = $(this).closest(".dropped-field");
            openFieldEditor($field);
        });
    }

    function activateRemoveField() {
        $(".remove-field").off("click").on("click", function () {
            $(this).closest(".dropped-field").remove();
        });
    }

    function saveFieldOptions() {
        var $editField = $("#field-options-modal").data("editField");
        var fieldType = $editField ? $editField.data("field") : $("#field-options-modal").data("fieldType");
        var columnNumber = $("#field-options-modal").data("column");
        var rowNumber = $("#field-options-modal").data("row");
        var $droppable = $("#field-options-modal").data("droppable");

        var fieldTitle = $("#field-label").val().trim();
        var required = $("#field-required").is(":checked");
        var supportsOptions = (fieldType === "checkbox_group" || fieldType === "radio_group" || fieldType === "drop_down_list");
        var includeAlignment = (fieldType === "checkbox_group" || fieldType === "radio_group");

        if (fieldTitle === "") {
            alert("❌ Please enter a field label.");
            return;
        }

        var options = [];
        if (supportsOptions) {
            var optionsText = $("#field-options").val().trim();
            if (optionsText === "") {
                alert("❌ Please enter options (comma separated).");
                return;
            }
            options = optionsText.split(",").map(function (opt) { return opt.trim(); });
        }

        var optionsAlignment = "";
        if (includeAlignment) {
            optionsAlignment = $("#field-options-alignment").val() || "vertical";
        }

        if ($editField) {
            if (supportsOptions) {
                $editField.attr("data-options", JSON.stringify(options));
                if (includeAlignment) {
                    $editField.attr("data-options-alignment", optionsAlignment)
                              .removeClass("vertical horizontal")
                              .addClass(optionsAlignment);
                }
            }
            $editField.attr("data-required", required ? "true" : "false");
            $editField.attr("data-title", fieldTitle);
            $editField.find(".field-title").text(fieldTitle);
            $("#field-options-modal").removeData("editField");
        } else {
            var newField = $("<div class='dropped-field" + (includeAlignment ? " " + optionsAlignment : "") + "' data-field='" + fieldType + "' data-column='" + columnNumber + "' data-row='" + rowNumber + "' data-title='" + fieldTitle + "'" +
                (supportsOptions ? " data-options='" + JSON.stringify(options) + "'" + (includeAlignment ? " data-options-alignment='" + optionsAlignment + "'" : "") : "") +
                " data-required='" + (required ? "true" : "false") + "'>" +
                "<span class='field-icon'>" + getFieldIcon(fieldType) + "</span> " +
                "<span class='field-title'>" + fieldTitle + "</span> " +
                "<span class='edit-field' style='cursor:pointer;'>✎</span> " +
                "<span class='remove-field' style='cursor:pointer;'>X</span></div>");
            $droppable.append(newField);
        }
        
        activateRemoveField();
        $("#field-options-modal").fadeOut();
        $("#field-label").val("");
        $("#field-options").val("");
        $('#field-options-alignment').val('vertical').show();
        $("label[for='field-options-alignment']").show();
        $("#options-container").hide();
        $("#field-required").prop("checked", false);
    }

    $("#save-field-options").click(function () {
        saveFieldOptions();
    });

    // Updated Cancel Event: simply close the modal without removing the field.
    $("#cancel-field-options").click(function () {
        $("#field-options-modal").fadeOut(function () {
            $("#field-label").val("");
            $("#field-options").val("");
            $("#field-required").prop("checked", false);
            $("#options-container").hide();
            $("#field-options-modal").removeData("editField");
        });
    });

    /*---------------------------------------------------------
      7. Form Creation, Loading, Deletion, and Saving
    ---------------------------------------------------------*/
    $("#create-new-form").click(function () {
        var newFormName = $("#new-form-name").val().trim();
        if (newFormName === "") {
            alert("❌ Please enter a form name.");
            return;
        }
        $.post(AminFormBuilder.ajax_url, {
            action: "create_new_form",
            form_name: newFormName
        }, function (response) {
            if (response.success) {
                var formID = response.data.form_id;
                alert(response.data.message);
                AminFormBuilder.saved_forms[formID] = [];
                $("#form-selector").append(new Option(newFormName, formID));
                $("#delete-form-selector").append(new Option(newFormName, formID));
                $("#form-selector").val(formID);
                $("#new-form-name").val("");
                $("#amin-form-rows").empty();
                addNewRow(1);
                // Show the Arrange Form Layout container
                $(".amin-form-rows-box").show();
            } else {
                alert("❌ Error creating form: " + response.data.message);
            }
        });
    });

    // When loading an existing form for editing
    $("#load-form").click(function () {
        var selectedFormID = $("#form-selector").val();
        if (!selectedFormID) {
            alert("❌ No form selected.");
            return;
        }
        loadSavedForm(selectedFormID);
        // Show the Arrange Form Layout container
        $(".amin-form-rows-box").show();
    });

    $("#delete-form").click(function () {
        var selectedFormID = $("#delete-form-selector").val();
        if (!selectedFormID) {
            alert("❌ Please select a form to delete.");
            return;
        }
        if (!confirm("⚠️ Are you sure you want to delete this form?")) {
            return;
        }
        $.post(AminFormBuilder.ajax_url, {
            action: "delete_amin_form",
            form_id: selectedFormID
        }, function (response) {
            if (response.success) {
                alert(response.data.message);
                delete AminFormBuilder.saved_forms[selectedFormID];
                loadSavedFormsList();
                $("#amin-form-rows").empty();
                addNewRow(1);
            } else {
                alert("❌ Error deleting form: " + response.data.message);
            }
        });
    });

    $("#save-amin-form").click(function () {
        var selectedFormID = $("#form-selector").val();
        var fields = [];
        $(".droppable-column .dropped-field").each(function () {
            fields.push({
                field: $(this).data("field"),
                column: $(this).closest(".form-column").data("column"),
                row: $(this).closest(".form-row").attr("id").split("-")[1],
                title: $(this).attr("data-title") || $(this).find(".field-title").text(),
                options: $(this).attr("data-options") ? JSON.parse($(this).attr("data-options")) : [],
                optionsAlignment: $(this).attr("data-options-alignment") || "vertical",
                required: $(this).attr("data-required") === "true"
            });
        });
        if (!selectedFormID) {
            alert("❌ Error: No form selected.");
            return;
        }
        if (fields.length === 0) {
            alert("❌ Error: No fields in the form.");
            return;
        }
        $.post(AminFormBuilder.ajax_url, {
            action: "save_amin_form",
            form_id: selectedFormID,
            fields: JSON.stringify(fields)
        }, function (response) {
            if (response.success) {
                alert(response.data.message || "✅ Form saved successfully.");
                AminFormBuilder.saved_forms[selectedFormID] = fields;
            } else {
                alert("❌ Error saving form: " + response.data.message);
            }
        });
    });

    loadSavedFormsList();
});
