<?php
ini_set("display_errors", 0);
include("config.php");
require_once("conexionDB.php");
session_start(); //Se inicia la sesión
$obj_con=new conectar;

require_once("class.TemplatePower.inc.php"); //Usando Template Power

date_default_timezone_set('America/Argentina/Buenos_Aires');

$tpl=new TemplatePower("perfil.html");
    $tpl->prepare();

$conexion= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion2= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());
$conexion3= new ConexionDB($obj_con->getServ(), $obj_con->getBase(), $obj_con->getUsr(), $obj_con->getPass());

$idSocio = $_SESSION['usuario'];
$accion=$_GET['a'];
$idPeriodoActual = obtenerPeriodoActual($conexion);

if ($accion == "") {
    $accion=$_POST['a'];
}

if ($idSocio == "") {
    header('Location: login.php');
} else {
    if ($accion == "p") { //Datos del usuario
        $conexion->Ejecuto("select s.Nombres, s.Apellidos, s.Documento, s.Direccion, s.Ciudad, s.FechaNac, s.Sexo, s.Email, s.Facebook, s.Telefono, s.ViveCon, s.Hospeda, c.Nombre as 'Club', d.Nombre as 'Distrito', s.FechaIngreso, r.Nombre as 'Rueda', s.AreaEstudio, s.Trabajo, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto, s.Admin from socio s, tiporueda r, club c, distrito d where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito and r.idTipoRueda=s.idTipoRueda");
        $datosSocio=$conexion->Siguiente();

        $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $idSocio . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
        $presidenteA=$conexion->Siguiente();

        $conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $idSocio . " and e.idEvento=ea.idEvento and e.Habilitado=1");
        $adminEventos=$conexion->Siguiente();

        $tpl->newBlock("menues");

        if ($idEvento != "") {
            $tpl->assign("miperfil", "<a href='perfil.php?a=p'>Mi perfil</a>");
        } else {
            $tpl->assign("miperfil", "<td width=\"149\" height=\"30\" class=\"EstiloMarcado\" bgcolor=\"#E4457D\">Mi perfil</td>");
        }

        $tpl->assign("inscripciones", "<a href='perfil.php?a=i'>Inscripciones</a>");

        $conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $idSocio . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosClub=$conexion->Siguiente()) {
            if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario') {
                $presidente = true;
                $tpl->newBlock("menuAprobacion");
                $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                $tpl->newBlock("menuCuadroSocial");
                $tpl->newBlock("menuStats");
                break;
            }
        }
        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Administrador') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }

        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Representante Distrital') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }

        if ($presidenteA['idSocio'] == $idSocio && !$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        if ($datosSocio['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
            $tpl->newBlock("menuEventos");
        }

        $idEvento = $_POST['idE'];

        if ($idEvento != "" || $_POST['idS'] != "") {
            $idSocio = $_POST['idS'];

            $conexion->Ejecuto("select s.Nombres, s.Apellidos, s.Direccion, s.Ciudad, s.Sexo, s.Facebook, s.FechaNac, s.Email, s.Telefono, c.Nombre as 'Club', d.Nombre as 'Distrito', s.FechaIngreso, r.Nombre as 'Rueda', s.AreaEstudio, s.Trabajo, s.ViveCon, s.Hospeda, s.NombreContacto, s.TelefonoContacto, s.RelacionContacto, s.Admin from socio s, tiporueda r, club c, distrito d where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito and r.idTipoRueda=s.idTipoRueda");
            $datosSocio=$conexion->Siguiente();
        }

        //Bloque datos personales
        $tpl->newBlock("personales");
        $tpl->assign("nombre", $datosSocio['Nombres'] . " " . $datosSocio['Apellidos']);
        $tpl->assign("documento", $datosSocio['Documento']);
        $tpl->assign("direccion", $datosSocio['Direccion']);
        $tpl->assign("ciudad", $datosSocio['Ciudad']);

        $fechaNac = split("-", $datosSocio['FechaNac']);

        $tpl->assign("fechaNac", $fechaNac[2] . "/" . $fechaNac[1] . "/" . $fechaNac[0]);

        if ($datosSocio['Sexo'] == 1) {
            $tpl->assign("sexo", "Femenino");
        } elseif ($datosSocio['Sexo'] == 2) {
            $tpl->assign("sexo", "Masculino");
        }

        $tpl->assign("mail", $datosSocio['Email']);
        $tpl->assign("facebook", $datosSocio['Facebook']);
        $tpl->assign("telefono", $datosSocio['Telefono']);
        $tpl->assign("viveCon", $datosSocio['ViveCon']);
        $tpl->assign("hospeda", convertirBit($datosSocio['Hospeda']));
        $tpl->assign("ocupacion", $datosSocio['AreaEstudio']);
        $tpl->assign("trabajo", $datosSocio['Trabajo']);
        $tpl->assign("nombreContacto", $datosSocio['NombreContacto']);
        $tpl->assign("telefonoContacto", $datosSocio['TelefonoContacto']);
        $tpl->assign("relacion", $datosSocio['RelacionContacto']);

        if ($idEvento != "") {
            $volver=$_GET['a2'];
            $tpl->newBlock("botonVolverEvento");

            if ($volver != "") {
                $tpl->assign("idEvento", $idEvento . "&a=v");
            } else {
                $tpl->assign("idEvento", $idEvento);
            }
        } else {
            $tpl->newBlock("botonesPerfil");
        }

        //Bloque datos en el club
        $tpl->newBlock("club");
        $tpl->assign("distrito", $datosSocio['Distrito']);
        $tpl->assign("club", $datosSocio['Club']);

        $fechaIng = split("-", $datosSocio['FechaIngreso']);

        $tpl->assign("fechaIng", $fechaIng[2] . "/" . $fechaIng[1] . "/" . $fechaIng[0]);
        $tpl->assign("rueda", $datosSocio['Rueda']);

        //Cargar cargos anteriores en el Club
        $conexion->Ejecuto("select cc.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargoclub h, periodo p, cargoclub cc where h.idSocio=" . $idSocio . " and p.idPeriodo=h.idPeriodo and cc.idCargoClub=h.idCargoClub order by p.idPeriodo DESC");
        $cantCargoClub=$conexion->Tamano();

        if ($cantCargoClub == 0) {
            $tpl->newBlock("cargoClub0");
        } else {
            while ($cargosClub=$conexion->Siguiente()) {
                $tpl->newBlock("cargoClub");
                $tpl->assign("cargoPasado", $cargosClub['Cargo']);
                $tpl->assign("periodo", $cargosClub['AnoInicio'] . "-" . $cargosClub['AnoFin']);
            }
        }

        //Bloque datos en el distrito
        $tpl->newBlock("distrito");

        $conexion->Ejecuto("select sum(h.VecesInstructor) as 'Total' from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=0");
        $instructorDistrito=$conexion->Siguiente();

        if ($instructorDistrito['Total'] == "") {
            $tpl->assign("veces", 0);
        } else {
            $tpl->assign("veces", $instructorDistrito['Total']);
        }

        //Cargar asistencia a eventos del Distrito
        $conexion->Ejecuto("select t.Nombre, h.CantidadAsistencias from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=0 order by t.Nombre ASC");

        while ($eventosDistrito=$conexion->Siguiente()) {
            $tpl->newBlock("eventoDistrito");
            $tpl->assign("evento", $eventosDistrito['Nombre']);
            $tpl->assign("cantidad", $eventosDistrito['CantidadAsistencias']);
        }

        //Cargar cargos anteriores en el Distrito
        $conexion->Ejecuto("select cd.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargodistrito h, periodo p, cargodistrito cd where h.idSocio=" . $idSocio . " and p.idPeriodo=h.idPeriodo and cd.idCargoDistrito=h.idCargoDistrito order by p.idPeriodo DESC");
        $cantCargoDistrito=$conexion->Tamano();

        if ($cantCargoDistrito == 0) {
            $tpl->newBlock("cargoDistrito0");
        } else {
            while ($cargosDistrito=$conexion->Siguiente()) {
                $tpl->newBlock("cargoDistrito");
                $tpl->assign("cargoPasado", $cargosDistrito['Cargo']);
                $tpl->assign("periodo", $cargosDistrito['AnoInicio'] . "-" . $cargosDistrito['AnoFin']);
            }
        }

        //Bloque datos en AIRAUP
        $tpl->newBlock("airaup");

        //Cargar asistencia a ERAUP
        $conexion->Ejecuto("select h.CantidadAsistencias, h.VecesInstructor from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='E.R.A.U.P.'");
        $cantERAUP=$conexion->Siguiente();

        //A partir de ERAUPs registrados en SGI
        $conexion->Ejecuto("select sum(case when Instructor=1 then 1 else 0 end) as 'Instructor' from historialmesaeraup where idSocio=" . $idSocio);
        $instructorV=$conexion->Siguiente();

        if ($cantERAUP['CantidadAsistencias'] == "") {
            $tpl->assign("cantERAUP", 0);
        } else {
            $tpl->assign("cantERAUP", $cantERAUP['CantidadAsistencias']);
            $tpl->assign("cantERAUPtotal", $cantERAUP['CantidadAsistencias'] + $cantERAUP['VecesInstructor'] + $instructorV['Instructor']);
        }

        if ($cantERAUP['VecesInstructor'] == "") {
            $tpl->assign("cantInstERAUP", 0);
        } else {
            $tpl->assign("cantInstERAUP", $cantERAUP['VecesInstructor'] + $instructorV['Instructor']);
        }

        //Cargar asistencia a Asambleas de AIRAUP
        $conexion->Ejecuto("select h.CantidadAsistencias from historialevento h, tipoevento t where h.idSocio=" . $idSocio . " and t.idTipoEvento=h.idTipoEvento and t.Tipo=1 and t.Nombre='Asamblea A.I.R.A.U.P.'");
        $cantAsambleas=$conexion->Siguiente();

        if ($cantAsambleas['CantidadAsistencias'] == 0) {
            $tpl->assign("cantAsamblea", 0);
        } else {
            $tpl->assign("cantAsamblea", $cantAsambleas['CantidadAsistencias']);
        }

        //Cargar cargos anteriores en AIRAUP
        $conexion->Ejecuto("select ca.Nombre as 'Cargo', p.AnoInicio, p.AnoFin from historialcargoairaup h, periodo p, cargoairaup ca where h.idSocio=" . $idSocio . " and p.idPeriodo=h.idPeriodo and ca.idCargoAIRAUP=h.idCargoAIRAUP order by p.idPeriodo DESC");
        $cantCargoAIRAUP=$conexion->Tamano();

        if ($cantCargoAIRAUP == 0) {
            $tpl->newBlock("cargoAIRAUP0");
        } else {
            while ($cargosAIRAUP=$conexion->Siguiente()) {
                $tpl->newBlock("cargoAIRAUP");
                $tpl->assign("cargoPasado", $cargosAIRAUP['Cargo']);
                $tpl->assign("periodo", $cargosAIRAUP['AnoInicio'] . "-" . $cargosAIRAUP['AnoFin']);
            }
        }

        //Cargar mesas ERAUP como asistente e instructor
        $conexion->Ejecuto("select Mesa, idSocio, Instructor from historialmesaeraup where idSocio=" . $idSocio);

        while ($mesaERAUP=$conexion->Siguiente()) {
            if ($mesaERAUP['Instructor'] == 0) {
                $tpl->newBlock("mesaERAUP");
                $tpl->assign("mesa", $mesaERAUP['Mesa']);
            }
        }

        //Bloque datos médicos
        $tpl->newBlock("medicos");

        $conexion->Ejecuto("select ObraSocial, NumeroSocio, GrupoSangre, Factor, EnfermedadCronica, EnfermedadCronicaE, Internacion3anos, Internacion3anosE, EnfermedadInfecciosa, EnfermedadInfecciosaE, IntervencionQuirurjica, IntervencionQuirurjicaE, Alergia, AlergiaE, Vegetariano, Dieta, Fuma, Lateralidad, Lentes, Audifonos, LimitacionFisica, LimitacionFisicaE, DonanteOrganos, DonanteMedula, NombreMedicamento, Droga, CantidadMedicamento from datosmedicos where idSocio=" . $idSocio);
        $datosMedicos=$conexion->Siguiente();

        $tpl->assign("obraSocial", $datosMedicos['ObraSocial']);
        $tpl->assign("numSocio", $datosMedicos['NumeroSocio']);
        $tpl->assign("grupoS", $datosMedicos['GrupoSangre']);

        if ($datosMedicos['Factor'] == 0) {
            $tpl->assign("factor", "-");
        } elseif ($datosMedicos['Factor'] == 1) {
            $tpl->assign("factor", "+");
        }

        $tpl->assign("enfCronica", convertirBit($datosMedicos['EnfermedadCronica']));
        $tpl->assign("internado", convertirBit($datosMedicos['Internacion3anos']));
        $tpl->assign("infeccion", convertirBit($datosMedicos['EnfermedadInfecciosa']));
        $tpl->assign("intervencion", convertirBit($datosMedicos['IntervencionQuirurjica']));
        $tpl->assign("alergia", convertirBit($datosMedicos['Alergia']));
        $tpl->assign("enfCronicaE", $datosMedicos['EnfermedadCronicaE']);
        $tpl->assign("internadoE", $datosMedicos['Internacion3anosE']);
        $tpl->assign("infeccionE", $datosMedicos['EnfermedadInfecciosaE']);
        $tpl->assign("intervencionE", $datosMedicos['IntervencionQuirurjicaE']);
        $tpl->assign("alergiaE", $datosMedicos['AlergiaE']);
        $tpl->assign("vegetariano", convertirBit($datosMedicos['Vegetariano']));

        if ($datosMedicos['Dieta'] == "") {
            $tpl->assign("dieta", "Ninguna");
        } else {
            $tpl->assign("dieta", $datosMedicos['Dieta']);
        }

        $tpl->assign("fumador", convertirBit($datosMedicos['Fuma']));

        if ($datosMedicos['Lateralidad'] == 1) {
            $tpl->assign("lateralidad", "Diestro");
        } elseif ($datosMedicos['Lateralidad'] == 0) {
            $tpl->assign("lateralidad", "Zurdo");
        }

        $tpl->assign("lentes", convertirBit($datosMedicos['Lentes']));
        $tpl->assign("audifonos", convertirBit($datosMedicos['Audifonos']));
        $tpl->assign("limitacion", convertirBit($datosMedicos['LimitacionFisica']));
        $tpl->assign("limitacionE", $datosMedicos['LimitacionFisicaE']);
        $tpl->assign("donanteOrganos", convertirBit($datosMedicos['DonanteOrganos']));
        $tpl->assign("donanteMedula", convertirBit($datosMedicos['DonanteMedula']));
        $tpl->assign("nombre", $datosMedicos['NombreMedicamento']);
        $tpl->assign("monodroga", $datosMedicos['Droga']);
        $tpl->assign("cantidadAdministrada", $datosMedicos['CantidadMedicamento']);
    } elseif ($accion == "a") { //Lista de eventos con inscriptos a aprobar
        $conexion->Ejecuto("select s.Admin, s.idClub, d.idDistrito from socio s, distrito d, club c where s.idSocio=" . $idSocio . " and s.idClub=c.idClub and c.idDistrito=d.idDistrito");
        $admin=$conexion->Siguiente();

        $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $idSocio . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
        $presidenteA=$conexion->Siguiente();

        $conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $idSocio . " and e.idEvento=ea.idEvento and e.Habilitado=1");
        $adminEventos=$conexion->Siguiente();

        $presidente = false;
        $representante = false;

        $tpl->newBlock("menues");
        $tpl->assign("miperfil", "<a href='perfil.php?a=p'>Mi perfil</a>");

        $tpl->assign("inscripciones", "<a href='perfil.php?a=i'>Inscripciones</a>");

        $conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $idSocio . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosClub=$conexion->Siguiente()) {
            if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario') {
                $presidente = true;
                $tpl->newBlock("menuAprobacion");
                $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                $tpl->newBlock("menuCuadroSocial");
                $tpl->newBlock("menuStats");
                break;
            }
        }
        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Administrador') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }
        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Representante Distrital') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }

        if ($presidenteA['idSocio'] == $idSocio && !$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        if ($admin['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
            $tpl->newBlock("menuEventos");
        }

        if (!$presidente && !$representante) {
            header('Location: perfil.php?a=p');
        }

        if ($presidente) {
            $conexion->Ejecuto("select e.idEvento, e.Nombre from evento e, inscripcionevento i, tipoevento t, socio s, eventodistrito ed where e.idTipoEvento=t.idTipoEvento and e.idEvento=ed.idEvento and ed.idDistrito=" . $admin['idDistrito'] . " and t.Tipo=0 and e.idEvento=i.idEvento and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and i.idSocio=s.idSocio and s.idClub=" . $admin['idClub'] . " group by e.idEvento");
            $cantidadEventos=$conexion->Tamano();

            $tpl->newBlock("aprobarInscripciones");

            if ($cantidadEventos > 0) {
                while ($eventosAprobar=$conexion->Siguiente()) {
                    $organiza = obtenerOrganizadores($conexion2, $eventosAprobar['idEvento'], "", false);

                    $tpl->newBlock("eventoAprobar");
                    $tpl->assign("idEvento", $eventosAprobar['idEvento']);
                    $tpl->assign("evento", $eventosAprobar['Nombre'] . " - " . $organiza);
                }
            } else {
                $tpl->newBlock("mensajeAprobaciones");
                $tpl->assign("mensaje", "No tenes aprobaciones pendientes en el Club");
            }
        }

        if ($representante) {
            $cantidadEventos = 0;

            $conexion->Ejecuto("select e.idEvento, e.Nombre from evento e, tipoevento t, eventodistrito ed
where e.idTipoEvento=t.idTipoEvento and ed.idDistrito=" . $admin['idDistrito'] . " and ed.idEvento=e.idEvento and t.Tipo=0
group by e.idEvento");

            if (!$presidente) {
                $tpl->newBlock("aprobarInscripciones");
            }

            $primera = true;
            $cantDistritos0 = 0;
            $distritos = "";

            while ($eventosDistritalesAprobar=$conexion->Siguiente()) {
                $conexion2->Ejecuto("select ed.idDistrito as 'Distrito' from evento e, eventodistrito ed where e.idEvento=" . $eventosDistritalesAprobar['idEvento'] . " and e.idEvento=ed.idEvento");
                $cantDistritosO=$conexion2->Tamano();

                if ($cantDistritos0 == 1) {
                    $eventoO=$conexion2->Siguiente();
                    $distritos=$eventoO['Distrito'];
                } else {
                    for ($x=1;$x<=$cantDistritosO;$x++) {
                        $eventoO=$conexion2->Siguiente();

                        if ($x<$cantDistritosO) {
                            $distritos.= "'" . $eventoO['Distrito'] . "',";
                        } else {
                            $distritos.= "'" . $eventoO['Distrito'] . "'";
                        }
                    }
                }

                $conexion2->Ejecuto("select count(i.idInscripcion) as 'Pendientes' from inscripcionevento i, socio s, club c where i.idEvento=" . $eventosDistritalesAprobar['idEvento'] . " and i.idSocio=s.idSocio and i.Eliminado=0 and (i.Aprobado=0 or i.Aprobado=3) and ((s.idClub=c.idClub and c.Nombre='Otro' and c.idDistrito=" . $admin['idDistrito'] . ") or (s.idClub=c.idClub and c.idDistrito not in (" . $distritos . ")) or (s.idTipoRueda=1 or s.idTipoRueda=3)) group by s.idSocio order by s.idSocio ASC");
                $cantidadPendientes=$conexion2->Siguiente();

                if ($cantidadPendientes['Pendientes'] > 0) {
                    $cantidadEventos++;
                    /*if ($primera){
                        $tpl->newBlock("aprobarInscripciones");
                        $primera = false;
                    }*/

                    $organiza = obtenerOrganizadores($conexion2, $eventosDistritalesAprobar['idEvento'], "", false);

                    $tpl->newBlock("eventoAprobar");
                    $tpl->assign("idEvento", $eventosDistritalesAprobar['idEvento']);
                    $tpl->assign("evento", $eventosDistritalesAprobar['Nombre'] . " - " . $organiza);
                }
            }

            $conexion->Ejecuto("select e.idEvento, e.Nombre from inscripcionevento i, socio s, club c, distrito d, evento e, tipoevento t, eventodistrito ed where i.idSocio=s.idSocio and i.idEvento=e.idEvento and e.idTipoEvento=t.idTipoEvento and t.Tipo=1 and (i.Aprobado=0 or i.Aprobado=3) and i.Eliminado=0 and ((ed.idDistrito=" . $admin['idDistrito'] . " and e.idEvento=ed.idEvento and (s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro')) or (s.idClub=c.idClub and c.idDistrito=d.idDistrito and c.idDistrito=" . $admin['idDistrito'] . ")) group by e.idEvento");
            $hayEventos=$conexion->Tamano();

            if ($hayEventos > 0) {
                /*if ($primera){
                    $tpl->newBlock("aprobarInscripciones");
                    $primera = false;
                }*/

                while ($eventosMultiDistritalesAprobar=$conexion->Siguiente()) {
                    $cantidadEventos++;
                    $organiza = obtenerOrganizadores($conexion2, $eventosMultiDistritalesAprobar['idEvento'], "", false);

                    $tpl->newBlock("eventoAprobar");
                    $tpl->assign("idEvento", $eventosMultiDistritalesAprobar['idEvento']);
                    $tpl->assign("evento", $eventosMultiDistritalesAprobar['Nombre'] . " - " . $organiza);
                }
            }

            if ($cantidadEventos == 0) {
                $tpl->newBlock("mensajeAprobaciones");
                $tpl->assign("mensaje", "No tenes aprobaciones pendientes en el Distrito");
            }
        }
    } elseif ($accion == "i") { //Lista de eventos disponibles para inscribirse
        $conexion->Ejecuto("select Admin, idTipoRueda from socio where idSocio=" . $idSocio);
        $admin=$conexion->Siguiente();

        $conexion->Ejecuto("select h.idSocio from historialcargoairaup h, cargoairaup c where h.idSocio=" . $idSocio . " and c.Nombre='Presidente' and c.idCargoAIRAUP=h.idCargoAIRAUP and h.idPeriodo=" . $idPeriodoActual);
        $presidenteA=$conexion->Siguiente();

        $conexion->Ejecuto("select count(*) as 'Cantidad' from eventoadmin ea, evento e where ea.idSocio=" . $idSocio . " and e.idEvento=ea.idEvento and e.Habilitado=1");
        $adminEventos=$conexion->Siguiente();

        $presidente = false;
        $representante = false;

        $tpl->newBlock("menues");
        $tpl->assign("miperfil", "<a href='perfil.php?a=p'>Mi perfil</a>");

        $tpl->assign("inscripciones", "<td width=\"149\" height=\"30\" class=\"EstiloMarcado\" bgcolor=\"#E4457D\">Inscripciones</td>");

        $conexion->Ejecuto("select c.Nombre from historialcargoclub h, cargoclub c, periodo where h.idSocio=" . $idSocio . " and c.idCargoClub=h.idCargoClub and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosClub=$conexion->Siguiente()) {
            if ($cargosClub['Nombre'] == 'Presidente' || $cargosClub['Nombre'] == 'Secretario') {
                $presidente = true;
                $tpl->newBlock("menuAprobacion");
                $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                $tpl->newBlock("menuCuadroSocial");
                $tpl->newBlock("menuStats");
                break;
            }
        }
        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Administrador') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }
        $conexion->Ejecuto("select c.Nombre from historialcargodistrito h, cargodistrito c where h.idSocio=" . $idSocio . " and c.idCargoDistrito=h.idCargoDistrito and h.idPeriodo=" . $idPeriodoActual);

        while ($cargosDistrito=$conexion->Siguiente()) {
            if ($cargosDistrito['Nombre'] == 'Representante Distrital') {
                $representante = true;

                if (!$presidente) {
                    $tpl->newBlock("menuAprobacion");
                    $tpl->assign("aprobacion", "<a href='perfil.php?a=a'>Aprobaciones</a>");
                    $tpl->newBlock("menuCuadroSocial");
                    $tpl->newBlock("menuStats");
                }
                break;
            }
        }

        if ($presidenteA['idSocio'] == $idSocio && !$presidente && !$representante) {
            $tpl->newBlock("menuStats");
        }

        if ($admin['Admin'] == 1 || $representante || $adminEventos['Cantidad'] > 0 || $presidenteA['idSocio'] == $idSocio) {
            $tpl->newBlock("menuEventos");
        }

        $tpl->newBlock("inscripcionesDisponibles");
        $cantidadTotal = 0;

        $conexion->Ejecuto("select idDistrito, Nombre from distrito order by Nombre ASC");
        $primero = true;

        $hoy = date("Y-m-d H:i:s");

        while ($distritos=$conexion->Siguiente()) {
            if ($primero) {
                $tpl->newBlock("tituloDistrital");
                $primero = false;
            }

            //Cargar eventos distritales
            $conexion2->Ejecuto("select e.idEvento, e.Nombre, e.CupoMaximo, e.FechaInicio, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.Habilitado from evento e, eventodistrito ed, tipoevento t where e.idTipoEvento=t.idTipoEvento and t.Tipo=0 and ed.idDistrito=" . $distritos['idDistrito'] . " and ed.idEvento=e.idEvento and e.FechaInicioInscripcion<='" . $hoy .  "' and ed.idEvento in (select idEvento from eventodistrito group by idEvento having count(idDistrito)=1) group by e.idEvento order by e.FechaInicio DESC limit 6");

            $cantidadEventos=$conexion2->Tamano();

            if ($cantidadEventos > 0) {
                $tpl->newBlock("nombreDistrito");
                $tpl->assign("distrito", $distritos['Nombre']);
                desplegarEvento($conexion2, $conexion3, $tpl, $presidente, $representante, "D", $idSocio);
                $cantidadTotal += $cantidadEventos;
                $tpl->newBlock("lineaVaciaD");
            }
        }

        $cantidadTotal+= $cantidadEventos;

        if ($cantidadTotal == 0) {
            $tpl->newBlock("lineaVaciaD");
        }

        //Cargar eventos distritales organizados por más de un distrito
        $conexion->Ejecuto("select e.idEvento from evento e, eventodistrito ed, tipoevento t where e.idTipoEvento=t.idTipoEvento and t.Tipo=0 and ed.idEvento=e.idEvento and e.FechaInicioInscripcion<='" . $hoy . "' and ed.idEvento in (select idEvento from eventodistrito group by idEvento having count(idDistrito)>1) group by e.idEvento order by e.FechaInicio DESC, e.idEvento, ed.idDistrito");
        $cantidadE=$conexion->Tamano();

        $matriz[0] = "";
        $primera = true;

        while ($eventosO2=$conexion->Siguiente()) {
            $conexion2->Ejecuto("select d.Nombre from eventodistrito e, distrito d where e.idEvento=" . $eventosO2['idEvento'] . " and d.idDistrito=e.idDistrito order by Nombre ASC");
            $cantD=$conexion2->Tamano();
            $nombre = "Distritos ";
            $x = 1;

            while ($distritos=$conexion2->Siguiente()) {
                if ($x<$cantD) {
                    $nombre.= $distritos['Nombre'] . "/";
                } else {
                    $nombre.= $distritos['Nombre'];
                }

                $x++;
            }

            $existe = false;

            for ($x=0;$x<count($matriz);$x++) {
                if ($matriz[$x][0] == $nombre) {
                    $pos = count($matriz[$x]);
                    $matriz[$x][$pos] = $eventosO2['idEvento'];
                    $existe = true;
                    break;
                }
            }

            if (!$existe) {
                if ($primera) {
                    $pos = 0;
                    $primera = false;
                } else {
                    $pos = count($matriz);
                }

                $matriz[$pos][0] = $nombre;
                $matriz[$pos][1] = $eventosO2['idEvento'];
            }
        }

        if ($cantidadE > 0) {
            $cantidadTotal += $cantidadE;
            $tpl->newBlock("tituloDistrital2");

            for ($x=0;$x<count($matriz);$x++) {
                $tpl->newBlock("nombreDistritos");
                $tpl->assign("distritos", $matriz[$x][0]);

                for ($i=1;$i<count($matriz[$x]);$i++) {
                    $conexion->Ejecuto("select idEvento, Nombre, CupoMaximo, FechaInicio, FechaInicioInscripcion, FechaFinInscripcion, Habilitado from evento where idEvento=" . $matriz[$x][$i]);
                    $eventosDisponibles=$conexion->Siguiente();

                    $conexion2->Ejecuto("select Aprobado, Eliminado from inscripcionevento where idEvento=" . $eventosDisponibles['idEvento'] . " and idSocio=" . $idSocio);
                    $estado=$conexion2->Siguiente();
                    $tpl->assign("idEventoDistritos", $eventosDisponibles['idEvento']);


                    $tpl->newBlock("eventoDisponibleD2");

                    if ($estado['Eliminado'] == 0 || $estado['Eliminado'] == null) {
                        if ($estado['Aprobado'] == null && $eventosDisponibles['FechaInicioInscripcion'] <= $hoy && $eventosDisponibles['FechaFinInscripcion'] >= $hoy && $eventosDisponibles['Habilitado'] == 1) {
                            $conexion2->Ejecuto("select count(idInscripcion) as 'Cantidad' from inscripcionevento where idEvento=" . $eventosDisponibles['idEvento'] . " and Aprobado in (0,1,3,4) and Eliminado=0");
                            $inscriptos=$conexion2->Siguiente();

                            $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . "</a>");

                            if ($inscriptos['Cantidad'] > $eventosDisponibles['CupoMaximo']) {
                                $tpl->assign("estado", "No inscripto - CUPO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                            } else {
                                $tpl->assign("estado", "No inscripto");
                            }
                        } elseif ($estado['Aprobado'] == null) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "No inscripto");
                        } elseif ($estado['Aprobado'] == 0) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "Pendiente de aprobacion");
                        } elseif ($estado['Aprobado'] == 1) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "Aprobado");
                        } elseif ($estado['Aprobado'] == 2) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "Reprobado");
                        } elseif ($estado['Aprobado'] == 3) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "En lista de espera");
                        } elseif ($estado['Aprobado'] == 4) {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                            $tpl->assign("estado", "Aprobado, en lista de espera");
                        }
                    } elseif ($estado['Eliminado'] == 1) {
                        $tpl->assign("evento", $eventosDisponibles['Nombre']);
                        $tpl->assign("estado", utf8_encode("Inscripción eliminada"));
                    }

                    $fecha = split(" ", $eventosDisponibles['FechaInicio']);
                    $fechaI = split("-", $fecha[0]);

                    $tpl->assign("fecha", $fechaI[2] . "/" . $fechaI[1] . "/" . $fechaI[0]);

                    if ($eventosDisponibles['FechaFinInscripcion'] > $hoy) {
                        $fechaC = split(" ", $eventosDisponibles['FechaFinInscripcion']);
                        $fechaCF = split("-", $fechaC[0]);

                        $tpl->assign("cierre", "Fecha de cierre: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                    } else {
                        $tpl->assign("cierre", utf8_encode("Inscripción cerrada"));
                    }

                    if ($representante) {
                        $tpl->assign("verI", "- <a href='inscriptos.php?a=v&id=" . $eventosDisponibles['idEvento'] . "' class='negrita'>Ver inscriptos de mi Distrito</a>");
                    } elseif ($presidente) {
                        $tpl->assign("verI", "- <a href='inscriptos.php?a=v&id=" . $eventosDisponibles['idEvento'] . "' class='negrita'>Ver inscriptos de mi Club</a>");
                    }
                }

                if ($x < count($matriz) - 1) {
                    $tpl->newBlock("lineaVaciaD2");
                }
            }
        }

        //Cargar eventos AIRAUP
        $conexion->Ejecuto("select e.idEvento, e.Nombre, e.CupoMaximo, e.FechaInicio, e.FechaInicioInscripcion, e.FechaFinInscripcion, e.FechaInicioInscripcion2, e.FechaFinInscripcion2, e.PorcentajeRotarios1, e.PorcentajeRotarios2, e.PorcentajeExtranjeros1, e.PorcentajeExtranjeros2, e.Habilitado, e.idTipoEvento from evento e, tipoevento t where e.idTipoEvento=t.idTipoEvento and e.FechaInicioInscripcion<='" . $hoy . "' and t.Tipo=1 order by e.FechaInicio DESC limit 20");
        $cantidadEventos=$conexion->Tamano();

        if ($cantidadEventos > 0) {
            $tpl->newBlock("tituloAIRAUP");
            desplegarEvento($conexion, $conexion2, $tpl, $presidente, $representante, "A", $idSocio);
        }

        $cantidadTotal+= $cantidadEventos;

        if ($cantidadTotal == 0) {
            $tpl->newBlock("mensajeEventos");
            $tpl->assign("mensaje", "No hay eventos disponibles en este momento.");
        }
    }
}

$conexion->Libero();
$conexion2->Libero(); //Se cierra la conexión a la base
$conexion3->Libero();
$tpl->printToScreen(); //Se manda todo al HTML usando TPL

function desplegarEvento($conex, $conex2, $tpl, $presidente, $representante, $tipoE, $idSocio)
{
    $hoy = date("Y-m-d H:i:s");

    $conex2->Ejecuto("select idTipoEvento from tipoevento where Nombre='E.R.A.U.P.'");
    $eraup=$conex2->Siguiente();

    while ($eventosDisponibles=$conex->Siguiente()) {
        if ($tipoE == "A") {
            $distritos = obtenerOrganizadores($conex2, $eventosDisponibles['idEvento'], "", false);
        }

        $conex2->Ejecuto("select d.idDistrito, d.Nombre, t.Nombre as 'Rueda' from distrito d, club c, socio s, tiporueda t where s.idClub=c.idClub and c.idDistrito=d.idDistrito and s.idTipoRueda=t.idTipoRueda and s.idSocio=" . $idSocio);
        $distrito=$conex2->Siguiente();

        if (!($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento'] && $distrito['Rueda'] == "Interact")) {
            if (!($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento'] && $eventosDisponibles['Habilitado'] == 0) && !($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento'] && !strpos($distritos, $distrito['Nombre']) && $eventosDisponibles['FechaInicioInscripcion'] > $hoy)) {
                $conex2->Ejecuto("select Aprobado, Eliminado, FechaInscripcion from inscripcionevento where idEvento=" . $eventosDisponibles['idEvento'] . " and idSocio=" . $idSocio);
                $estado=$conex2->Siguiente();

                $tpl->newBlock("eventoDisponible" . $tipoE);

                if ($estado['Eliminado'] == 0 || $estado['Eliminado'] == null) {
                    if ($estado['Aprobado'] == null && $eventosDisponibles['FechaInicioInscripcion'] <= $hoy && $eventosDisponibles['FechaFinInscripcion'] >= $hoy && $eventosDisponibles['Habilitado'] == 1) { //En caso de ERAUP representa FASE I
                        $conex2->Ejecuto("select count(idInscripcion) as 'Cantidad' from inscripcionevento where idEvento=" . $eventosDisponibles['idEvento'] . " and Aprobado<>2 and Eliminado=0");
                        $inscriptos=$conex2->Siguiente();

                        if ($tipoE == "A") {
                            $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . " - " . $distritos . "</a>");
                        } else {
                            $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . "</a>");
                        }

                        if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento']) {
                            if ($distrito['Rueda'] == "Rotary" && $distrito['Nombre'] != 'Otro') {
                                $conex2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s where s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $eventosDisponibles['idEvento']);
                                $cantidadR=$conex2->Siguiente();

                                $conex2->Ejecuto("select PorcentajeRotarios1, CupoMaximo from evento where idEvento=" . $eventosDisponibles['idEvento']);
                                $valores=$conex2->Siguiente();

                                $cupoRotario = ($valores['PorcentajeRotarios1'] * $valores['CupoMaximo']) / 100;

                                if ($cupoRotario > 0 && $cantidadR['Cantidad'] >= $cupoRotario && !strpos($distritos, $distrito['Nombre'])) {
                                    $tpl->assign("estado", "No inscripto - CUPO PARA ROTARIOS RESERVADO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                } else {
                                    $tpl->assign("estado", "No inscripto");
                                }
                            } elseif ($distrito['Nombre'] == 'Otro') {
                                $conex2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $eventosDisponibles['idEvento']);
                                $cantidadE=$conex2->Siguiente();

                                $conex2->Ejecuto("select PorcentajeExtranjeros1, CupoMaximo from evento where idEvento=" . $eventosDisponibles['idEvento']);
                                $valores=$conex2->Siguiente();

                                $cupoExtranjeros = ($valores['PorcentajeExtranjeros1'] * $valores['CupoMaximo']) / 100;

                                if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] >= $cupoExtranjeros) {
                                    $tpl->assign("estado", "No inscripto - CUPO PARA EXTRANJEROS RESERVADO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                } else {
                                    $tpl->assign("estado", "No inscripto");
                                }
                            } else {
                                $conex2->Ejecuto("select CupoReservado as 'Cupo', Inscriptos from asistenciaeraup where idEvento=" . $eventosDisponibles['idEvento'] . " and idDistrito=" . $distrito['idDistrito']);
                                $cupo=$conex2->Siguiente();

                                //De inscriptos restar los que estén marcados como reserva
                                $conex2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idTipoRueda=2 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.Reserva=1 and s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.idDistrito=" . $distrito['idDistrito'] . " and i.idEvento=" . $eventosDisponibles['idEvento']);
                                $marcados=$conex2->Siguiente();

                                if (($cupo['Inscriptos'] - $marcados['Cantidad']) >= $cupo['Cupo']) {
                                    if (strpos($distritos, $distrito['Nombre'])) {
                                        $tpl->assign("estado", "No inscripto");
                                    } else {
                                        $tpl->assign("estado", "No inscripto - CUPO DISTRITAL RESERVADO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                    }
                                } else {
                                    $tpl->assign("estado", "No inscripto");
                                }
                            }
                        } else {
                            if ($inscriptos['Cantidad'] > $eventosDisponibles['CupoMaximo']) {
                                $tpl->assign("estado", "No inscripto - CUPO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                            } else {
                                $tpl->assign("estado", "No inscripto");
                            }
                        }
                    } else {
                        if ($estado['Aprobado'] == null) {
                            if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento'] && $eventosDisponibles['FechaInicioInscripcion2'] <= $hoy && $eventosDisponibles['FechaFinInscripcion2'] >= $hoy) {
                                //FASE II
                                if ($distrito['Rueda'] == "Rotary" && $distrito['Nombre'] != 'Otro') {
                                    $conex2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s where s.idTipoRueda=3 and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $eventosDisponibles['idEvento']);
                                    $cantidadR=$conex2->Siguiente();

                                    $conex2->Ejecuto("select PorcentajeRotarios2, CupoMaximo from evento where idEvento=" . $eventosDisponibles['idEvento']);
                                    $valores=$conex2->Siguiente();

                                    $cupoRotario = ($valores['PorcentajeRotarios2'] * $valores['CupoMaximo']) / 100;

                                    if ($inscriptos['Cantidad'] >= $eventosDisponibles['CupoMaximo']) {
                                        $tpl->assign("estado", "No inscripto - CUPO GLOBAL AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                    } else {
                                        if ($cupoRotario > 0 && $cantidadR['Cantidad'] >= $cupoRotario && !strpos($distritos, $distrito['Nombre'])) {
                                            $tpl->assign("estado", "No inscripto - CUPO PARA ROTARIOS RESERVADO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                        } else {
                                            $tpl->assign("estado", "No inscripto");
                                        }
                                    }
                                } elseif ($distrito['Nombre'] == 'Otro') {
                                    $conex2->Ejecuto("select count(i.idSocio) as 'Cantidad' from inscripcionevento i, socio s, club c, distrito d where s.idClub=c.idClub and c.idDistrito=d.idDistrito and d.Nombre='Otro' and s.idSocio=i.idSocio and i.Eliminado=0 and i.Aprobado<>2 and i.idEvento=" . $eventosDisponibles['idEvento']);
                                    $cantidadE=$conex2->Siguiente();

                                    $conex2->Ejecuto("select PorcentajeExtranjeros2, CupoMaximo from evento where idEvento=" . $eventosDisponibles['idEvento']);
                                    $valores=$conex2->Siguiente();

                                    $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;

                                    if ($inscriptos['Cantidad'] >= $eventosDisponibles['CupoMaximo']) {
                                        $tpl->assign("estado", "No inscripto - CUPO GLOBAL AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                    } else {
                                        if ($cupoExtranjeros > 0 && $cantidadE['Cantidad'] >= $cupoExtranjeros) {
                                            $tpl->assign("estado", "No inscripto - CUPO PARA EXTRANJEROS RESERVADO AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                        } else {
                                            $tpl->assign("estado", "No inscripto");
                                        }
                                    }
                                } else {
                                    if ($inscriptos['Cantidad'] >= $eventosDisponibles['CupoMaximo'] && !strpos($distritos, $distrito['Nombre'])) {
                                        $tpl->assign("estado", "No inscripto - CUPO GLOBAL AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                    } else {
                                        if (strpos($distritos, $distrito['Nombre'])) {
                                            $tpl->assign("estado", "No inscripto");
                                        } else {
                                            $conex2->Ejecuto("select PorcentajeRotarios2, PorcentajeExtranjeros2, CupoMaximo, Reserva from evento where idEvento=" . $eventosDisponibles['idEvento']);
                                            $valores=$conex2->Siguiente();

                                            $cupoRotario = ($valores['PorcentajeRotarios2'] * $valores['CupoMaximo']) / 100;
                                            $cupoExtranjeros = ($valores['PorcentajeExtranjeros2'] * $valores['CupoMaximo']) / 100;
                                            $disponible = $valores['CupoMaximo'] - $valores['Reserva'] - $cupoRotario - $cupoExtranjeros;

                                            if ($disponible == 0) {
                                                $tpl->assign("estado", "No inscripto - CUPO GLOBAL AGOTADO (podes inscribirte igual y quedar en lista de espera)");
                                            } else {
                                                $tpl->assign("estado", "No inscripto");
                                            }
                                        }
                                    }
                                }
                            } else {
                                $tpl->assign("estado", "No inscripto");
                            }
                        } elseif ($estado['Aprobado'] == 0) {
                            $tpl->assign("estado", "Pendiente de aprobacion");
                        } elseif ($estado['Aprobado'] == 1) {
                            $tpl->assign("estado", "Aprobado");
                        } elseif ($estado['Aprobado'] == 2) {
                            $tpl->assign("estado", "Reprobado");
                        } elseif ($estado['Aprobado'] == 3) {
                            $tpl->assign("estado", "En lista de espera");
                        } elseif ($estado['Aprobado'] == 4) {
                            $tpl->assign("estado", "Aprobado, en lista de espera");
                        }

                        if ($tipoE == "A") {
                            if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento']) { //SEGUNDA FASE ERAUP
                                if ($eventosDisponibles['FechaInicioInscripcion2'] <= $hoy && $eventosDisponibles['FechaFinInscripcion2'] >= $hoy) {
                                    if ($estado['Aprobado'] == null) {
                                        $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . " - " . $distritos . "</a>");
                                    } else {
                                        $tpl->assign("evento", $eventosDisponibles['Nombre'] . " - " . $distritos);
                                    }
                                } else {
                                    if (strpos($distritos, $distrito['Nombre'])) { //PARA DISTRITO ORGANIZADOR ABIERTA SIEMPRE
                                        if ($eventosDisponibles['FechaFinInscripcion2'] >= $hoy && $estado['Aprobado'] == null) {
                                            $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . " - " . $distritos . "</a>");
                                        } else {
                                            $tpl->assign("evento", $eventosDisponibles['Nombre'] . " - " . $distritos);
                                        }
                                    } else {
                                        $tpl->assign("evento", $eventosDisponibles['Nombre'] . " - " . $distritos);
                                    }
                                }
                            } else {
                                $tpl->assign("evento", $eventosDisponibles['Nombre'] . " - " . $distritos);
                            }
                        } else {
                            $tpl->assign("evento", $eventosDisponibles['Nombre']);
                        }
                    }
                } elseif ($estado['Eliminado'] == 1) {
                    if ($tipoE == "A") {
                        if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento'] && $eventosDisponibles['FechaInicioInscripcion2'] <= $hoy && $eventosDisponibles['FechaFinInscripcion2'] >= $hoy && $estado['FechaInscripcion'] <= $eventosDisponibles['FechaFinInscripcion']) {
                            $tpl->assign("evento", "<a href='inscripcion.php?idEvento=" . $eventosDisponibles['idEvento'] . "'>" . $eventosDisponibles['Nombre'] . " - " . $distritos . "</a>");
                            $tpl->assign("estado", utf8_encode("Inscripción eliminada en Fase I"));
                        } else {
                            $tpl->assign("evento", $eventosDisponibles['Nombre'] . " - " . $distritos);
                            $tpl->assign("estado", utf8_encode("Inscripción eliminada"));
                        }
                    } else {
                        $tpl->assign("evento", $eventosDisponibles['Nombre']);
                        $tpl->assign("estado", utf8_encode("Inscripción eliminada"));
                    }
                }

                $fecha = split(" ", $eventosDisponibles['FechaInicio']);
                $fechaI = split("-", $fecha[0]);

                $tpl->assign("fecha", $fechaI[2] . "/" . $fechaI[1] . "/" . $fechaI[0]);

                if ($eventosDisponibles['FechaFinInscripcion'] > $hoy && $eventosDisponibles['Habilitado'] == 1) {
                    $fechaC = split(" ", $eventosDisponibles['FechaFinInscripcion']);
                    $fechaCF = split("-", $fechaC[0]);

                    if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento']) {
                        if (strpos($distritos, $distrito['Nombre'])) {
                            //SI ES DEL DISTRITO ORGANIZADOR LLEVA FECHA FINAL DE INSCRIPCIÓN
                            $fechaC = split(" ", $eventosDisponibles['FechaFinInscripcion2']);
                            $fechaCF = split("-", $fechaC[0]);

                            $tpl->assign("cierre", "Fecha de cierre: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                        } else {
                            if ($eventosDisponibles['FechaInicioInscripcion'] <= $hoy) {
                                $tpl->assign("cierre", "Fecha de cierre Fase I: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                            }
                        }
                    } else {
                        $tpl->assign("cierre", "Fecha de cierre: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                    }
                } else {
                    if ($eventosDisponibles['Habilitado'] == 0) {
                        $tpl->assign("cierre", utf8_encode("EVENTO DESHABILITADO"));
                    } else {
                        if ($eraup['idTipoEvento'] == $eventosDisponibles['idTipoEvento']) {
                            if (strpos($distritos, $distrito['Nombre'])) {
                                //SI ES DEL DISTRITO ORGANIZADOR LLEVA FECHA FINAL DE INSCRIPCIÓN
                                $fechaC = split(" ", $eventosDisponibles['FechaFinInscripcion2']);
                                $fechaCF = split("-", $fechaC[0]);

                                if ($eventosDisponibles['FechaFinInscripcion2'] > $hoy) {
                                    $tpl->assign("cierre", "Fecha de cierre: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                                } else {
                                    $tpl->assign("cierre", utf8_encode("Inscripción cerrada"));
                                }
                            } else {
                                if ($eventosDisponibles['FechaInicioInscripcion2'] > $hoy && $eventosDisponibles['Habilitado'] == 1) {
                                    $fechaC = split(" ", $eventosDisponibles['FechaInicioInscripcion2']);
                                    $fechaCF = split("-", $fechaC[0]);

                                    $tpl->assign("cierre", "Fase I cerrada. Fecha de apertura Fase II: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                                } elseif ($eventosDisponibles['FechaInicioInscripcion2'] < $hoy && $eventosDisponibles['FechaFinInscripcion2'] > $hoy && $eventosDisponibles['Habilitado'] == 1) {
                                    $fechaC = split(" ", $eventosDisponibles['FechaFinInscripcion2']);
                                    $fechaCF = split("-", $fechaC[0]);

                                    $tpl->assign("cierre", "Fecha de cierre Fase II: " . $fechaCF[2] . "/" . $fechaCF[1] . "/" . $fechaCF[0]);
                                } elseif ($eventosDisponibles['FechaFinInscripcion2'] < $hoy && $eventosDisponibles['Habilitado'] == 1) {
                                    $tpl->assign("cierre", utf8_encode("Inscripción cerrada"));
                                }
                            }
                        } else {
                            $tpl->assign("cierre", utf8_encode("Inscripción cerrada"));
                        }
                    }
                }

                if ($representante) {
                    $tpl->assign("verI", "- <a href='inscriptos.php?a=v&id=" . $eventosDisponibles['idEvento'] . "' class='negrita'>Ver inscriptos de mi Distrito</a>");
                } elseif ($presidente) {
                    $tpl->assign("verI", "- <a href='inscriptos.php?a=v&id=" . $eventosDisponibles['idEvento'] . "' class='negrita'>Ver inscriptos de mi Club</a>");
                }
            }
        }
    }
}

function obtenerOrganizadores($conexion, $idEvento, $idBuscado, $soloID)
{
    $conexion->Ejecuto("select d.Nombre, d.idDistrito from distrito d, evento e, eventodistrito ed where ed.idEvento=" . $idEvento . " and ed.idDistrito=d.idDistrito and e.idEvento="  . $idEvento . " order by Nombre ASC");
    $cantDistritos=$conexion->Tamano();

    if ($cantDistritos>1) {
        $contar=1;

        if ($soloID) {
            $nombreDistrito = "";
        } else {
            $nombreDistrito = "Distritos ";
        }

        while ($distritos=$conexion->Siguiente()) {
            if ($idBuscado != "" && $distritos['idDistrito'] == $idBuscado) {
                return "SI";
            } else {
                if ($contar<$cantDistritos) {
                    if ($soloID) {
                        $nombreDistrito .= $distritos['idDistrito'] . ",";
                    } else {
                        $nombreDistrito .= $distritos['Nombre'] . " / ";
                    }
                } else {
                    if ($soloID) {
                        $nombreDistrito .= $distritos['idDistrito'];
                    } else {
                        $nombreDistrito .= $distritos['Nombre'];
                    }
                }
            }

            $contar++;
        }
    } else {
        $distritoO=$conexion->Siguiente();

        if ($idBuscado != "" && $distritoO['idDistrito'] == $idBuscado) {
            return "SI";
        } else {
            if ($soloID) {
                $nombreDistrito = $distritoO['idDistrito'];
            } else {
                $nombreDistrito = "Distrito " . $distritoO['Nombre'];
            }
        }
    }

    return $nombreDistrito;
}

function obtenerPeriodoActual($conexion)
{
    $anoActual = date('Y');
    $mesActual = date('m');

    if ($mesActual > 6) {
        $sentencia = "select idPeriodo from periodo where AnoInicio=" . $anoActual;
    } elseif ($mesActual <= 6) {
        $sentencia = "select idPeriodo from periodo where AnoInicio=" . ($anoActual - 1);
    }

    $conexion->Ejecuto($sentencia);
    $periodo = $conexion->Siguiente();
    return $periodo['idPeriodo'];
}

function convertirBit($dato)
{
    if ($dato == 0) {
        return "No";
    } elseif ($dato == 1) {
        return "Si";
    }
}
