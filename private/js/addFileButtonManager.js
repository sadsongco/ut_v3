const updateFileId = (id = null) => {
  const fileId = document.getElementById('fileId');
  fileId.value = parseInt(fileId.value) + 1;
};

const startUploadProgress = () => {
  const uploadProgressEl = document.getElementById('uploadProgress');
  uploadProgressEl.setAttribute('hx-trigger', 'every 0.5s');
};
