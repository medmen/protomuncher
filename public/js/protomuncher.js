function toggleVisibility(targetid) {
    let el = document.getElementById(targetid);
    if (el.style.visibility == "hidden") {
        el.style.visibility = "visible";
    } else {
        el.style.visibility = "hidden";
    }
}

function startUpload() {
    document.getElementById('waiting').style.visibility = 'visible';
    return true;
}

function stopUpload(success) {
    document.getElementById('waiting').style.visibility = 'hidden';
    var results = '';
    var step = 1;
    if (success == 1){
        document.getElementById('results').innerHTML +=
            '<span class="msg">The file was uploaded successfully!<\/span><br/><br/>';
        ajax_init_step('step2');
    } else {
        document.getElementById('results').innerHTML +=
            '<span class="emsg">There was an error during file upload!<\/span><br/><br/>';
    }
    return true;
}