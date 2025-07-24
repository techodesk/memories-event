<?php
class Translation
{
    private $translations = [
        'en' => [
            'events' => 'Events',
            'add_event' => 'Add Event',
            'name' => 'Name',
            'date' => 'Date',
            'location' => 'Location',
            'header_image' => 'Header Image',
            'description' => 'Description',
            'action' => 'Action',
            'status' => 'Status',
            'edit' => 'Edit',
            'delete_confirm' => 'Delete this event?',
            'edit_event' => 'Edit Event',
            'public_url' => 'Public URL',
            'regenerate' => 'Regenerate',
            'custom_css' => 'Custom CSS',
            'save_changes' => 'Save Changes',
            'guests_for_this_event' => 'Guests for This Event',
            'add_guests' => 'Add Guests',
            'add_selected' => 'Add Selected',
            'remove_guest' => 'Remove guest?',
            'use_camera' => 'Use Camera',
            'choose_file' => 'Choose File',
            'write_message' => 'Write a message...',
            'upload_btn' => 'Upload',
            'memories' => 'Memories',
            'add_memory' => 'Add Memory',
            'loading' => 'Loading...',
            'like' => 'Like',
            'unlike' => 'Unlike',
            'take_photo' => 'Take Photo',
            'language' => 'Language'
        ],
        'es' => [
            'events' => 'Eventos',
            'add_event' => 'Agregar Evento',
            'name' => 'Nombre',
            'date' => 'Fecha',
            'location' => 'Ubicación',
            'header_image' => 'Imagen de cabecera',
            'description' => 'Descripción',
            'action' => 'Acción',
            'status' => 'Estado',
            'edit' => 'Editar',
            'delete_confirm' => '¿Eliminar este evento?',
            'edit_event' => 'Editar Evento',
            'public_url' => 'URL Pública',
            'regenerate' => 'Regenerar',
            'custom_css' => 'CSS Personalizado',
            'save_changes' => 'Guardar Cambios',
            'guests_for_this_event' => 'Invitados a este Evento',
            'add_guests' => 'Agregar Invitados',
            'add_selected' => 'Agregar Seleccionados',
            'remove_guest' => '¿Eliminar invitado?',
            'use_camera' => 'Usar cámara',
            'choose_file' => 'Elegir archivo',
            'write_message' => 'Escribe un mensaje...',
            'upload_btn' => 'Subir',
            'memories' => 'Recuerdos',
            'add_memory' => 'Agregar Recuerdo',
            'loading' => 'Cargando...',
            'like' => 'Me gusta',
            'unlike' => 'No me gusta',
            'take_photo' => 'Tomar foto',
            'language' => 'Idioma'
        ],
    ];

    private $lang = 'en';

    public function __construct()
    {
        if (isset($_GET['lang']) && isset($this->translations[$_GET['lang']])) {
            $this->lang = $_GET['lang'];
            $_SESSION['lang'] = $this->lang;
        } elseif (isset($_SESSION['lang']) && isset($this->translations[$_SESSION['lang']])) {
            $this->lang = $_SESSION['lang'];
        }
    }

    public function t(string $key): string
    {
        if (isset($this->translations[$this->lang][$key])) {
            return $this->translations[$this->lang][$key];
        }
        return $this->translations['en'][$key] ?? $key;
    }

    public function getLang(): string
    {
        return $this->lang;
    }
}
