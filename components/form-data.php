<div class="max-w-xl mx-auto mt-[4%]">
  <h1 class="inline-flex items-center gap-1.5 font-semibold text-lg">
    <i class='bx bx-file-blank text-[1.4rem]'></i>
    Upload Your Files
  </h1>
  <div class="bg-white rounded-2xl shadow-sm mt-4 p-10">
    <form action="?page=uploaded-file" method="post" enctype="multipart/form-data">
      <div class="space-y-4">
        <div class="relative">
          <label class="text-gray-700 text-sm">
            Name
            <span class="text-red-500 required-dot">
              *
            </span>
          </label>
          <input type="text" name="name" class="mt-2 rounded-lg flex-1 appearance-none border border-gray-200 w-full py-1.5 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-[#28a2ff] focus:border-transparent" required />
        </div>
        <div class=" relative ">
          <label class="text-gray-700 text-sm">
            Class
            <span class="text-red-500 required-dot">
              *
            </span>
          </label>
          <input type="text" name="class" class="mt-2 rounded-lg flex-1 appearance-none border border-gray-200 w-full py-1.5 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-[#28a2ff] focus:border-transparent" required />
        </div>
        <div class="relative">
          <label class="text-gray-700 text-sm">
            Choose File
            <span class="text-red-500 required-dot">
              *
            </span>
          </label>
          <input type="file" name="files[]" id="file-input" accept=".pdf,.doc,.docx" multiple class="mt-2 block w-full border border-gray-200 shadow-sm rounded-lg text-sm focus:z-10 focus:border-[#28a2ff] focus:ring-[#28a2ff] disabled:opacity-50 disabled:pointer-events-none file:bg-gray-50 file:border-0 file:me-4 file:py-2 file:px-4 cursor-pointer">
        </div>
      </div>
      <div class="mt-10 flex justify-end">
        <button type="submit" class="w-fit py-2 px-4 bg-green-600 hover:bg-green-700 text-white transition ease-in duration-200 text-center text-sm font-semibold shadow-md focus:outline-none rounded-lg ">
          Submit
        </button>

      </div>
    </form>
  </div>
</div>