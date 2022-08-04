jQuery.ajax(`${myvars.endpoint}/hamburger`, {
  method: "POST",
  data: {
    [myvars.nonceKey]: myvars.nonce,
  },
  success(data) {
    var response = JSON.parse(data);

    console.log(response);
  },
});
