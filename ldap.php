
<!-- IMPORTAMOS LOS ESTILOS CSS Y LOS ARCHIVOS JS -->
<link rel="stylesheet"
      href="utils/css/bootstrap.min.css">
<link rel="stylesheet"
     href="utils/css/bootstrap-theme.min.css">
<script src="utils/js/jquery.min.js"></script>
<script src="utils/js/bootstrap.min.js"></script>

<?php
  //desactivamos los erroes por seguridad
  error_reporting(0);
  //error_reporting(E_ALL); //activar los errores (en modo depuración)

  $servidor_LDAP = " 52.24.210.244";      //IP DEL SERVIDOR
  $puerto = "389";                        //PUERTO DE CONEXIÓN
  $servidor_dominio = "toca.cat";         //DOMINIO
  $ldap_dn = "dc=toca,dc=cat";            //NOMBRE DEL DOMINIO
  $usuario_LDAP = $_POST['login'];        //NOMBRE DEL USUARIO LOGADO
  $contrasena_LDAP = $_POST['password'];  //CONTRASEÑA DEL USUARIO LOGADO

  

  $conectado_LDAP = ldap_connect($servidor_LDAP,$puerto); //CONECTAMOS CON SERVIDOR LDAP DESDE PHP
  ldap_set_option($conectado_LDAP, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($conectado_LDAP, LDAP_OPT_REFERRALS, 0);

  if ($conectado_LDAP) 
  {
    //CONECTADO CORRECTAMENTE CON LDAP

    $autenticado_LDAP = ldap_bind($conectado_LDAP, $usuario_LDAP . "@" . $servidor_dominio, $contrasena_LDAP);    //COMPROBAMOS USUARIO Y CONTRASEÑA EN SERVIDOR LDAP
    if ($autenticado_LDAP)
    {
         //AUTENTICACIÓN CORRECTA

        $sr=ldap_search($conectado_LDAP, "OU=ibadia,DC=toca,DC=cat", "ObjectClass=group");    //FILTRAMOS POR ObjectClass=group
        //El resultado de la búsqueda es $sr

        //El número de entradas devueltas es ldap_count_entries($conectado_LDAP, $sr);

        //Obteniendo entradas ...
        $info = ldap_get_entries($conectado_LDAP, $sr);

        $grupos = array();  //ARRAY PARA LOS GRUPOS DEL USUARIO LOGADO

        $arr = array();
        //OBTENEMOS LOS GRUPOS A LOS QUE PERTENECE EL USUARIO LOGADO
        for ($i=0; $i<$info["count"]; $i++) {
          for ($j=0; $j<$info[$i]["member"]; $j++) {
                if($info[$i]["member"][$j]!=null){
                    $pattern = "/[=,]/";  //filtro para split, hacemos split desde "=" hasta ","
                    array_push($arr,preg_split($pattern,$info[$i]["member"][$j])[1]); //[1] = la posicion del nombre del grupo
                    
                    if($arr[$j]==$usuario_LDAP){ //Si el usuario pertenece a este grupo
                      array_push($grupos,$info[$i]["cn"][0]);  //metemos el grupo en el array
                    }
                }else{
                    break;
                }
            }
            $arr = array(); //vaciamos el array para que no queden los miembros del grupo anterior
        };

        //COMPROBAMOS SI EL USUARIO LOGADO PERTENECE AL GRUPO SYSOPS
        $sysops = false;
        if(in_array("sysops",$grupos)){ //SI PERTENECE AL GRUPO SYSOPS
          $sysops = true; //MAS ADELANTE PINTAREMOS TODOS LOS MIEMBROS DE TODOS LOS GRUPOS CON ESTA VARIABLE
        }
        
        $id = 0;  //ID PARA PONERLE A LA CABECERA DE CADA ELEMENTO DE ACORDEON
        echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">'; //ABRIMOS EL ACORDEON
        echo '<div class="panel panel-info">';
        for ($i=0; $i<$info["count"]; $i++) {
            
              echo '<div class="panel-heading" role="tab" id="heading'.(++$id).'">';  //ABRIMOS UN HEADING POR CADA GRUPO
                echo '<h4 class="panel-title">
                  <a data-toggle="collapse" data-parent="#accordion" href="#collapse'.$id.'" aria-expanded="false" aria-controls="collapse'.$id.'">
                    ' . $info[$i]["cn"][0] . '
                  </a>
                </h4>
              </div>';
              if(in_array($info[$i]["cn"][0],$grupos) || $sysops){  //SI EL GRUPO PERTENECE A SYSOPS, O EL GRUPO ESTÁ DENTRO DEL ARRAY DE GRUPOS DEL USUARIO
                //MOSTRAMOS LOS MIEMBROS DE DICHO GRUPO
                echo '<div id="collapse'.$id.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$id.'">
                  <div class="panel-body">
					         <table class="table table-striped">
        						<tr>
        							<th>Miembros</th>
        						</tr>';   //MOSTRAMOS LOS MIEMROS EN UNA TABLA

                for ($j=0; $j<$info[$i]["member"]; $j++) {    
                  if($info[$i]["member"][$j]!=null){
                        echo "<tr><td>" . $info[$i]["member"][$j] . "</td></tr>";   //MOSTRAMOS EL MIEMBRO DEL GRUPO
                  }else{
                      break;
                  }
                  
                }
                echo '</table>
                      </div>
                    </div>';
              }
        }
        echo '</div>
            </div>';
       }
    else
    {
      //USUARIO O CONTRASEÑA INCORRECTO
      header('Location: index.html');  //REDIRECCIONAMOS A LOGIN
    }
  }
  else {
    //ERROR AL CONECTAR CON LDAP
    echo "<br><br>No se ha podido realizar la conexión con el servidor LDAP: ".$servidor_LDAP;
  }
?>