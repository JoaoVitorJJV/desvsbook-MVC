<style>
    .row label input {
        padding:5px;
        width:400px;
        border:1px solid #CCC;
    }
</style>

<?= $render('header', ['loggedUser' => $loggedUser]); ?>
    <section class="container main">
        <?=$render('sidebar', ['activeMenu' => 'profile']);?>
        <section class="feed mt-10">

        <div class="row" style="margin-bottom:15px">
            <div class="config" style="display:flex; flex-direction:column">
                <h1> Configurações </h1>
                
                <div class="config_files" style="margin:20px 0px; display:flex; flex-direction:column"> 
                    <span style="margin-bottom:10px;">Novo avatar</span>
                    <input type='file' name='avatar'/>
                </div>
                <div class="config_files_capa" style="display:flex; flex-direction:column">
                <span style="margin-bottom:10px;">Nova capa</span>
                    <input type='file' name='capa'/>
                </div>
            </div>

        </div>
        <hr/>
        <br/>
        <div class="row">
            <form method="POST" action="<?=$base;?>/config">
                <label> Nome Completo<br/>
                    <input type="text" name="name" value="<?=$user->name;?>"/>
                </label>
                <br/><br/>
                <label> Data de nascimento<br/>
                    <input type="text" name="birthdate" value="<?=$birthdate;?>"/>
                </label>
                <br/><br/>
                <label> Email<br/>
                    <input type="email" name="email" value="<?=$user->email;?>"/>
                </label><br/><br/>
                <label> Cidade<br/>
                    <input type="text" name="city" value="<?=$user->city;?>"/>
                </label><br/><br/>
                <label> Trabalho <br/>
                    <input type="text" name="work" value="<?=$user->work;?>"/>
                </label><br/><br/>
                <label> Nova senha<br/>
                    <input type="text" name="password"/>
                </label><br/><br/>
                <label> Confirmar senha<br/>
                    <input type="text" name="confirm_password"/>
                </label><br/><br/>

                <input class="button" type="submit" value="Salvar"/>
            </form> 
        </div>

    </section>
</section>
    

<?= $render('footer'); ?>