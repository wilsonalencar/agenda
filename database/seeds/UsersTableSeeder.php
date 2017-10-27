<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $data = array(
                        array('Fabiano Sbaratta','fabiano.sbaratta@maxtax.com.br','qwerty'),
                        array('Regina Celia Ribas Calil Luiz','regina.calil@innovative.com.br','123456'),
                        array('Bruna Souza','bruna.souza@innovative.com.br','123456'),
                        array('Maitê Souza de Almeida','maite.almeida@innovative.com.br','123456'),
                        array('Barbara Rodrigues Navarro','barbara.navarro@innovative.com.br','123456'),
                        array('Cristiane Borges de Sousa','cristiane.sousa@innovative.com.br','123456'),
                        array('Geise Graciela Baldan Gouveia','geise.navarro@innovative.com.br','123456'),
                        array('Leandro Cardoso Aparecido','leandro.aparecido@innovative.com.br','123456'),
                        array('Patricia de Macedo Fonseca Felipe','patricia.felipe@innovative.com.br','123456'),
                        array('Rafael Lopes de Alencar','rafael.alencar@innovative.com.br','123456'),
                        array('Floriano Silva Souza Junior','floriano.souza@innovative.com.br','123456'),
                        array('Marco Balbino','marco.balbino@innovative.com.br','123456'),
                        array('Anderson Gomes','anderson.gomes@innovative.com.br','123456'),
                        array('Ana Calharani','ana.calharani@innovative.com.br','123456'),
                        array('Viviane Rocha','viviane.rocha@innovative.com.br','123456'),
                        array('Delielson Gabriel dos Santos','delielson.santos@innovative.com.br','123456'),
                        array('Carla Gasparini','carla.gasparini@innovative.com.br','123456'),
                        array('Silvio Ginzaga da Silva','silvio.silva@innovative.com.br','123456'),
                        array('Michele dos Santos Alves','michele.alves@innovative.com.br','123456'),
                        array('Luana Ruachel Santos Malaquias','luana.malaquias@innovative.com.br','123456'),
                        array('Aline Fernanda Queiroz Santos','aline.santos@innovative.com.br','123456')
        );

        foreach ($data as $el) {
            DB::table('users')->insert([
                'name' => $el[0],
                'email' => $el[1],
                'password' => bcrypt($el[2]),
                'created_at' => '2016-02-01 00:00:00',
            ]);
        }

        $owner = new Role();
        $owner->name = 'owner';
        $owner->display_name = 'Project Owner'; // optional
        $owner->description = 'User is the owner of the project'; // optional
        $owner->created_at = '2016-02-01 00:00:00';
        $owner->save();

        $admin = new Role();
        $admin->name = 'admin';
        $admin->display_name = 'Administrador'; // optional
        $admin->description = 'User is allowed to manage and edit other users and configuration'; // optional
        $admin->created_at = '2016-02-01 00:00:00';
        $admin->save();

        $manager = new Role();
        $manager->name = 'manager';
        $manager->display_name = 'Manager'; // optional
        $manager->description = 'Manager is allowed to interact with the management dashboard'; // optional
        $manager->created_at = '2016-02-01 00:00:00';
        $manager->save();

        $supervisor = new Role();
        $supervisor->name = 'supervisor';
        $supervisor->display_name = 'Supervisor'; // optional
        $supervisor->description = 'User is allowed to access approval section'; // optional
        $supervisor->created_at = '2016-02-01 00:00:00';
        $supervisor->save();

        $analyst = new Role();
        $analyst->name = 'analyst';
        $analyst->display_name = 'Analista'; // optional
        $analyst->description = 'User is allowed to access delivery section'; // optional
        $analyst->created_at = '2016-02-01 00:00:00';
        $analyst->save();

        $user = new Role();
        $user->name = 'user';
        $user->display_name = 'Usuário'; // optional
        $user->description = 'User is allowed to access only basic informations'; // optional
        $user->created_at = '2016-02-01 00:00:00';
        $user->save();

        $owner_usr = User::where('email', '=', 'fabiano.sbaratta@maxtax.com.br')->first();
        // role attach alias
        $owner_usr->attachRole($owner); // parameter can be an Role object, array, or id
        // or eloquent's original technique
        //$owner_usr->roles()->attach($owner->id); // id only

        $admin_usr = User::where('email', '=', 'bruna.souza@innovative.com.br')->first();
        $admin_usr->attachRole($admin);

        $manager_usr = User::where('email', '=', 'regina.calil@innovative.com.br')->first();
        $manager_usr->attachRole($manager);

        $analyst_usr = User::where('email', '=', 'maite.almeida@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'barbara.navarro@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'geise.gouveia@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'cristiane.sousa@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'leandro.aparecido@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'patricia.felipe@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'rafael.alencar@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'floriano.souza@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'marco.balbino@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'anderson.gomes@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'ana.carolina@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'viviane.rocha@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'delielson.santos@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'carla.gasparini@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'luana.malaquias@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'silvio.silva@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'michele.alves@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);

        $analyst_usr = User::where('email', '=', 'aline.santos@innovative.com.br')->first();
        $analyst_usr->attachRole($analyst);
        /*
        $editUser = new Permission();
        $editUser->name         = 'edit-user';
        $editUser->display_name = 'Alterar configuração de Usuário'; // optional
        $editUser->description  = 'edit an existing user'; // optional
        $editUser->created_at = '2016-02-01 00:00:00';
        $editUser->save();

        $createCompany = new Permission();
        $createCompany->name         = 'create-company';
        $createCompany->display_name = 'Adicionar uma nova Empresa'; // optional
        $createCompany->description  = 'create a new company'; // optional
        $createCompany->created_at = '2016-02-01 00:00:00';
        $createCompany->save();

        $editCompany = new Permission();
        $editCompany->name         = 'edit-company';
        $editCompany->display_name = 'Alterar configuração de Empresa'; // optional
        $editCompany->description  = 'edit an existing company'; // optional
        $editCompany->created_at = '2016-02-01 00:00:00';
        $editCompany->save();

        $createSubsidiary = new Permission();
        $createSubsidiary->name         = 'create-subsidiary';
        $createSubsidiary->display_name = 'Adicionar um novo Estabelecimento'; // optional
        $createSubsidiary->description  = 'create a new subsidiary'; // optional
        $createSubsidiary->created_at = '2016-02-01 00:00:00';
        $createSubsidiary->save();

        $editSubsidiary = new Permission();
        $editSubsidiary->name         = 'edit-subsidiary';
        $editSubsidiary->display_name = 'Alterar configuração do Estabelecimento'; // optional
        $editSubsidiary->description  = 'edit an existing subsidiary'; // optional
        $editSubsidiary->created_at = '2016-02-01 00:00:00';
        $editSubsidiary->save();

        $owner->attachPermissions(array($editUser, $createCompany, $editCompany, $createSubsidiary, $editSubsidiary));
        $admin->attachPermissions(array($createCompany, $editCompany, $createSubsidiary, $editSubsidiary));
        */

    }
}
