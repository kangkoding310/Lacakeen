<script setup>
import { ref } from 'vue';
import vueFilePond from 'vue-filepond';

// Import core FilePond styles
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

// Import plugins
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
// import FilePondPluginImageCrop from 'filepond-plugin-image-crop';
// import FilePondPluginImageResize from 'filepond-plugin-image-resize';
// import FilePondPluginImageTransform from 'filepond-plugin-image-transform';

// Create the Vue component wrapping FilePond and its plugins
const FilePond = vueFilePond(
  FilePondPluginFileValidateType,
  FilePondPluginImagePreview,
//   FilePondPluginImageCrop,
//   FilePondPluginImageResize,
//   FilePondPluginImageTransform
);

const myFiles = ref([]);
const pondRef = ref(null);

// Configure backend server endpoints
const serverOptions = {
  url: 'https://your-api-endpoint.com',
  process: '/uploads/avatar', // POST request on file drop
  revert: '/uploads/avatar/revert', // DELETE request if canceled
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE'
  }
};
</script>

<template>
  <div class="avatar-upload-container">
    <file-pond
      ref="pondRef"
      name="avatar"
      label-idle="Drag & drop or <span class='filepond--label-action'>Browse</span>"
      :allow-multiple="false"
      accepted-file-types="image/jpeg, image/png, image/webp"
      :server="serverOptions"
      :files="myFiles"
      />
      <!-- :allow-image-crop="true"
      image-crop-aspect-ratio="1:1"
      :allow-image-resize="true"
      image-resize-target-width="300"
      image-resize-target-height="300"
      image-resize-mode="cover"
      :allow-image-transform="true" -->
  </div>
</template>

<style>
/* Shape FilePond wrapper into a classic circular avatar */
.avatar-upload-container {
  width: 150px;
  height: 150px;
  margin: 0 auto;
}

.avatar-upload-container .filepond--root {
  border-radius: 50%;
  overflow: hidden;
}

/* Force preview panel to match the circular mask */
.avatar-upload-container .filepond--panel-root {
  border-radius: 50%;
  background-color: #f1f1f1;
}

/* Adjust drop label placement for circular space */
.avatar-upload-container .filepond--drop-label {
  align-items: center;
  justify-content: center;
  font-size: 12px;
}
</style>
