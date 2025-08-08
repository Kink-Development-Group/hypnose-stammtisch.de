import { mount } from "svelte";
import "./app.css";
import App from "./App.svelte";
import "./utils/errorHandler"; // Global error handling

const app = mount(App, {
  target: document.getElementById("app")!,
});

export default app;
