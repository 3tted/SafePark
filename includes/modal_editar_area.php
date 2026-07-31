<div class="modal-overlay-admin" id="modal-editar-area" style="display:none;" onclick="if(event.target===this) cerrarEditarArea()">
    <div class="modal-card-admin">
        <div class="modal-title-admin">✏️ Editar área</div>
        <form action="<?= $accion_editar_area ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_area" id="edit-id">

            <div class="form-group">
                <label class="modal-label-admin">Nombre</label>
                <input class="modal-input-admin" type="text" name="nombre" id="edit-nombre" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label class="modal-label-admin">Colonia</label>
                <input class="modal-input-admin" type="text" name="colonia" id="edit-colonia" autocomplete="off" required>
            </div>

            <div class="form-group">
                <label class="modal-label-admin">Tipo</label>
                <select class="modal-input-admin" name="tipo" id="edit-tipo" required>
                    <option value="parque">🌳 Parque</option>
                    <option value="deportivo">⚽ Deportivo</option>
                    <option value="plaza">🏛️ Plaza</option>
                </select>
            </div>

            <?php if (!empty($mostrar_btn_ubicacion)): ?>
                <input type="hidden" name="lat" id="edit-lat">
                <input type="hidden" name="lng" id="edit-lng">
                <button type="button" class="btn-cancelar-modal-admin" style="width:100%;margin-bottom:14px;" onclick="activarEditarUbicacion()">📍 Cambiar ubicación en el mapa</button>
            <?php else: ?>
                <div class="modal-row-admin">
                    <div class="form-group" style="flex:1">
                        <label class="modal-label-admin">Latitud</label>
                        <input class="modal-input-admin" type="text" name="lat" id="edit-lat" autocomplete="off" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="modal-label-admin">Longitud</label>
                        <input class="modal-input-admin" type="text" name="lng" id="edit-lng" autocomplete="off" required>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="modal-label-admin">Foto (opcional, deja vacío para no cambiar)</label>
                <div class="foto-upload-area" id="drop-editar" onclick="document.getElementById('input-foto-editar').click()">
                    <div id="placeholder-editar">📷 Cambiar foto · JPG, PNG o WEBP · Máx. 3MB</div>
                    <img id="preview-editar" src="" alt="Preview" style="display:none;max-width:100%;max-height:180px;border-radius:8px;">
                </div>
                <input type="file" id="input-foto-editar" name="foto" accept="image/*" style="display:none" onchange="previewFotoMapa(this,'placeholder-editar','preview-editar')">
            </div>

            <div class="modal-btns-admin">
                <button type="button" class="btn-cancelar-modal-admin" onclick="cerrarEditarArea()">Cancelar</button>
                <button type="submit" class="btn-guardar-modal-admin">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
