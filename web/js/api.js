const API_URL = "http://localhost/club/api/";

async function llamarApi(endpoint, metodo = "GET", body = null) {
  const opciones = { method: metodo };
  if (body) {
    opciones.headers = { "Content-Type": "application/json" };
    opciones.body = JSON.stringify(body);
  }
  const respuesta = await fetch(API_URL + endpoint, opciones);
  const datos = await respuesta.json();
  if (!respuesta.ok) throw new Error(datos.error || "Error desconocido");
  return datos;
}

function guardarSesion(usuario, socio) {
  localStorage.setItem("usuario", JSON.stringify(usuario));
  localStorage.setItem("socio", JSON.stringify(socio));
}

function obtenerUsuario() {
  const dato = localStorage.getItem("usuario");
  return dato ? JSON.parse(dato) : null;
}

function esSocio() {
  const dato = localStorage.getItem("socio");
  return dato ? JSON.parse(dato) : null;
}

function cerrarSesion() {
  localStorage.removeItem("usuario");
  localStorage.removeItem("socio");
  window.location.href = "index.html";
}

function exigirSesion() {
  const usuario = obtenerUsuario();
  if (!usuario) {
    window.location.href = "index.html";
    return null;
  }
  return usuario;
}

function armarNav(paginaActual) {
  const usuario = obtenerUsuario();
  const nombre = usuario ? usuario.nombre : "";
  document.getElementById("nav").innerHTML = `
    <span class="marca">Club Universitario</span>
    <a href="eventos.html" ${paginaActual === "eventos" ? 'style="border-color:var(--naranja)"' : ""}>Eventos</a>
    <a href="mesas.html" ${paginaActual === "mesas" ? 'style="border-color:var(--naranja)"' : ""}>Mesas</a>
    <a href="perfil.html" ${paginaActual === "perfil" ? 'style="border-color:var(--naranja)"' : ""}>Mi perfil</a>
    <span style="color:var(--texto-suave); font-size:14px;">${nombre}</span>
    <button onclick="cerrarSesion()">Salir</button>
  `;
}