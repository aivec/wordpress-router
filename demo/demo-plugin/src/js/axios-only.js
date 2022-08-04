import axios from "axios";

const params = new URLSearchParams({ [myvars.nonceKey]: myvars.nonce });

axios.post(`${myvars.endpoint}/hamburger`, params).then(({ data }) => {
  console.log(data);
});
