function obtenerUbicacion() {
    if (navigator.geolocation) {
        document.getElementById("resultado").innerHTML = "Buscando ubicacion...";
        navigator.geolocation.getCurrentPosition(exito, error);
    } else {
        document.getElementById("resultado").innerHTML = "Tu navegador no soporta la geolocalizacion";
    }
}

function exito(posicion) {
    let latitud  = posicion.coords.latitude;
    let longitud = posicion.coords.longitude;
    document.getElementById("resultado").innerHTML = `
        <div class="coords"><strong>Latitud:</strong> ${latitud}<br><strong>Longitud:</strong> ${longitud}</div>
        <a href="https://www.google.com/maps?q=${latitud},${longitud}" target="_blank">Abrir en Google Maps</a>
        <iframe width="100%" height="100%"
            src="https://maps.google.com/maps?q=${latitud},${longitud}&z=15&output=embed">
        </iframe>
    `;
}

function error(err) {
    const mensajes = { 1:"Permiso denegado", 2:"Posicion no disponible", 3:"Tiempo agotado" };
    document.getElementById("resultado").innerHTML = "Error: " + (mensajes[err.code] || "Error desconocido");
}
