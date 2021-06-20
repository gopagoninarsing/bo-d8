(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.billing = {
    attach: function attach(context) {


      var bill_user = new Object();
      bill_user.updateUserInfo = function(userData) {
        $('.uname').html(userData.username);
        $('.utype').html(userData.type);
        $('.udob').html(userData.age);
        $('.ugender').html(userData.gender);
        $('.umobile').html(userData.mobile);
        $('.unephro').html(userData.primary_nephro);
        $('.udeposit').html(userData.deposit);
        $('.uinsurence').html(userData.insurance);
      }

      $('input[name=guest]', context).on('autocompleteselect', function (event, data) {
        var guest_name = data.item.value;
        bill_user.uid = guest_name.substring(guest_name.lastIndexOf("(") + 1, guest_name.lastIndexOf(")"));

        var url = drupalSettings.path.baseUrl + 'np/guest/bill-data/'+bill_user.uid;

        $.ajax({url: url,
          success: function(result) {
            bill_user.updateUserInfo(result);
            console.log('Actal result');
            console.log(result);
          }
        });

      });

      console.log(bill_user);



    }
  };
})(jQuery, Drupal, drupalSettings);
