<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    #[Route('/edit', name: 'app_user_edit')]
    #[Route('', name: 'app_user_edit')]
    public function edit(
        Request $request
    ): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            if (!$user) {
                throw new AccessDeniedException('Musisz być zalogowany, aby edytować swój profil.');
            }

            $form = $this->createForm(UserType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();
                $this->userService->updateProfile($user, $plainPassword);

                $this->addFlash('success', 'Twój profil został zaktualizowany.');
                return $this->redirectToRoute('app_user_edit');
            }

            return $this->render('user/edit.html.twig', [
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('home');
        }
    }

    #[Route('/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request
    ): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $csrfToken = $request->request->get('_token');

            $this->userService->deleteAccount($user, $csrfToken);

            $this->addFlash('success', 'Twoje konto zostało usunięte.');
            return $this->redirectToRoute('app_login');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_user_edit');
        }
    }
}