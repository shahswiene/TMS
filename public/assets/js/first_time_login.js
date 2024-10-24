document.addEventListener('DOMContentLoaded', function() {
    var input = document.querySelector("#phone_number");
    var iti = window.intlTelInput(input, {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        separateDialCode: true,
        initialCountry: "auto",
        geoIpLookup: function(callback) {
            fetch("https://ipapi.co/json")
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    callback(data.country_code);
                })
                .catch(function() {
                    callback("us");
                });
        },
    });

    // Ensure the full number (with country code) is submitted
    document.getElementById('firstTimeLoginForm').addEventListener('submit', function() {
        input.value = iti.getNumber();
    });
  
});