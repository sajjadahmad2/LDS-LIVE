 <!-- Bootstrap JS -->

 <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
 <!--plugins-->
 {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js')}}"></script> --}}
 <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
 <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
 <script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
 <script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
 <script src="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js') }}"></script>
 <script src="{{ asset('assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js') }}"></script>

 <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
 <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
 <script src="{{ asset('dist/js/dropify.min.js') }}"></script>
 <script src="https://unpkg.com/feather-icons"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
 <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
 <script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Select all input fields of type "number"
        document.querySelectorAll('input[type="number"]').forEach(function (input) {
            input.addEventListener("input", function () {
                if (parseFloat(this.value) < 0) {
                    this.value = ""; // Clear input if a negative number is entered
                }
            });
            input.addEventListener("keydown", function (event) {
                if (event.key === "-" || event.key === "e") {
                    event.preventDefault(); // Prevent typing negative sign and exponential notation
                }
            });
        });
    });
</script>
 <script>
     $(document).ready(function() {
        if (window.parent =! window.self) {
             let logo = document.querySelector('[id="app_sidebar_logo"]');
             document.querySelectorAll('.parent-icon').forEach(element => element.remove());
             logo.style.display = 'none';
             let menuItems = document.querySelectorAll('#menu li');
             let targetNav = document.querySelector('.top-menu_nav ul');
             menuItems.forEach((item) => {
                 let clonedItem = item.cloneNode(true);
                 clonedItem.classList.add('nav-item');
                 let anchor = clonedItem.querySelector('a');
                 if (anchor) {
                     anchor.classList.add('nav-link');
                 }
                 let iconDiv = clonedItem.querySelector('.parent-icon');
                 if (iconDiv) {
                     iconDiv.remove();
                 }
                 targetNav.appendChild(clonedItem);
             });
             let topHeader = document.querySelector('.top-menu_nav>ul');
             let mobileToggleMenu = document.querySelector('.mobile-toggle-menu');
             mobileToggleMenu.remove();
             topHeader.classList.add('horizontal');
             //footer
             let footer = document.querySelector('[class="page-footer"]');
             footer.remove();
             //search
             let serach = document.querySelector('[class="search-bar flex-grow-1"]');
             serach.remove();
             //remove the ms-auto class
             let topMenu = document.querySelector('.top-menu.ms-auto.top-menu_nav');
             topMenu.classList.remove('ms-auto');
             // Select the element with the class 'user-box dropdown'
             let userBox = document.querySelector('.user-box.dropdown');
             userBox.classList.add('ms-auto');
             document.querySelector('.sidebar-wrapper_ifram')?.remove();



             const pageWrapper = document.querySelector('.page-wrapper');
             if (pageWrapper) {
                 pageWrapper.style.height = '100%';
                 pageWrapper.style.marginTop = '0px';
                 pageWrapper.style.marginBottom = '30px';
                 pageWrapper.style.marginLeft = '0px';
             }
             const style = document.createElement('style');
             style.innerHTML = `
             .topbar {
                position: static !important;
                top: 0 !important;
                left: 250px !important;
                right: 0 !important;
                height: 60px !important;
                background: #fff !important;
                border-bottom: 1px solid rgba(228, 228, 228, 0%) !important;
                z-index: 10 !important;
                box-shadow: 0 2px 6px 0 rgba(218, 218, 253, 0.65), 0 0px 6px 0 rgba(206, 206, 238, 0.54) !important;
              }`;
             document.head.appendChild(style);
         }

         $(".mobile-search-icon").on("click", function() {
             $(".search-bar").addClass("full-search-bar")
         }), $(".search-close").on("click", function() {
             $(".search-bar").removeClass("full-search-bar")
         }), $(".mobile-toggle-menu").on("click", function() {
             $(".wrapper").addClass("toggled")
         }), $(".toggle-icon").click(function() {
             $(".wrapper").hasClass("toggled") ? ($(".wrapper").removeClass("toggled"), $(
                 ".sidebar-wrapper").unbind("hover")) : ($(".wrapper").addClass("toggled"), $(
                 ".sidebar-wrapper").hover(function() {
                 $(".wrapper").addClass("sidebar-hovered")
             }, function() {
                 $(".wrapper").removeClass("sidebar-hovered")
             }))
         }), $(document).ready(function() {
             $(window).on("scroll", function() {
                 $(this).scrollTop() > 300 ? $(".back-to-top").fadeIn() : $(".back-to-top")
                     .fadeOut()
             }), $(".back-to-top").on("click", function() {
                 return $("html, body").animate({
                     scrollTop: 0
                 }, 600), !1
             })
         }), $(function() {
             for (var e = window.location, o = $(".metismenu li a").filter(function() {
                     return this.href == e
                 }).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass(
                 "mm-show").parent("").addClass("mm-active")
         }), $(function() {
             $("#menu").metisMenu()
         }), $(".chat-toggle-btn").on("click", function() {
             $(".chat-wrapper").toggleClass("chat-toggled")
         }), $(".chat-toggle-btn-mobile").on("click", function() {
             $(".chat-wrapper").removeClass("chat-toggled")
         }), $(".email-toggle-btn").on("click", function() {
             $(".email-wrapper").toggleClass("email-toggled")
         }), $(".email-toggle-btn-mobile").on("click", function() {
             $(".email-wrapper").removeClass("email-toggled")
         }), $(".compose-mail-btn").on("click", function() {
             $(".compose-mail-popup").show()
         }), $(".compose-mail-close").on("click", function() {
             $(".compose-mail-popup").hide()
         }), $(".switcher-btn").on("click", function() {
             $(".switcher-wrapper").toggleClass("switcher-toggled")
         }), $(".close-switcher").on("click", function() {
             $(".switcher-wrapper").removeClass("switcher-toggled")
         }), $("#lightmode").on("click", function() {
             $("html").attr("class", "light-theme")
         }), $("#darkmode").on("click", function() {
             $("html").attr("class", "dark-theme")
         }), $("#semidark").on("click", function() {
             $("html").attr("class", "semi-dark")
         }), $("#minimaltheme").on("click", function() {
             $("html").attr("class", "minimal-theme")
         }), $("#headercolor1").on("click", function() {
             $("html").addClass("color-header headercolor1"), $("html").removeClass(
                 "headercolor2 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8"
             )
         }), $("#headercolor2").on("click", function() {
             $("html").addClass("color-header headercolor2"), $("html").removeClass(
                 "headercolor1 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8"
             )
         }), $("#headercolor3").on("click", function() {
             $("html").addClass("color-header headercolor3"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8"
             )
         }), $("#headercolor4").on("click", function() {
             $("html").addClass("color-header headercolor4"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor3 headercolor5 headercolor6 headercolor7 headercolor8"
             )
         }), $("#headercolor5").on("click", function() {
             $("html").addClass("color-header headercolor5"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor4 headercolor3 headercolor6 headercolor7 headercolor8"
             )
         }), $("#headercolor6").on("click", function() {
             $("html").addClass("color-header headercolor6"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor4 headercolor5 headercolor3 headercolor7 headercolor8"
             )
         }), $("#headercolor7").on("click", function() {
             $("html").addClass("color-header headercolor7"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor3 headercolor8"
             )
         }), $("#headercolor8").on("click", function() {
             $("html").addClass("color-header headercolor8"), $("html").removeClass(
                 "headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor3"
             )
         })

         // Basic
         $('.dropify').dropify();
         // Translated
         $('.dropify-fr').dropify({
             messages: {
                 default: 'Glissez-déposez un fichier ici ou cliquez',
                 replace: 'Glissez-déposez un fichier ou cliquez pour remplacer',
                 remove: 'Supprimer',
                 error: 'Désolé, le fichier trop volumineux'
             }
         });

         // Used events
         var drEvent = $('#input-file-events').dropify();
         drEvent.on('dropify.beforeClear', function(event, element) {
             return confirm("Do you really want to delete \"" + element.file.name + "\" ?");
         });
         drEvent.on('dropify.afterClear', function(event, element) {
             alert('File deleted');
         });

         drEvent.on('dropify.errors', function(event, element) {
             console.log('Has Errors');
         });
         var drDestroy = $('#input-file-to-destroy').dropify();
         drDestroy = drDestroy.data('dropify')
         $('#toggleDropify').on('click', function(e) {
             e.preventDefault();
             if (drDestroy.isDropified()) {
                 drDestroy.destroy();
             } else {
                 drDestroy.init();
             }
         })


         document.querySelectorAll('a.nav-link').forEach(t => {
                 if (t.getAttribute('href') == location.href) {
                     t.classList.add('active')
                 }
             })

     });

     // dashboard
 </script>
