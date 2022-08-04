import axios from "axios";
import { createRequestBody } from "@aivec/reqres-utils";

axios
  .post(`${myvars.endpoint}/hamburger`, createRequestBody(myvars))
  .then(({ data }) => {
    console.log(data);
  });
