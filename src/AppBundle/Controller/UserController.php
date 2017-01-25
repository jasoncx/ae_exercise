<?php

namespace AppBundle\Controller;

use AppBundle\Form\{PortfolioType,StockType,UserType};
use AppBundle\Entity\{Portfolio,Stock,User};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function registerAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            return $this->redirectToRoute('user_portfolios');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            // user registration, we can check here for duplicate username
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_portfolios');
        }

        return $this->render(
            'user/register.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/login", name="user_login")
     */
    public function loginAction(Request $request)
    {
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) 
        {
            return $this->redirectToRoute('user_portfolios');
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
     * @Route("/portfolios", name="user_portfolios")
     */
    public function portfolios(Request $request)
    {
        $error = false;

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $portfolio = new Portfolio();
        $form = $this->createForm(PortfolioType::class, $portfolio);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $portfolio->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($portfolio);
            try
            {
                $em->flush();
            } catch (\Doctrine\DBAL\DBALException $e)
            {
                $error = 'Portfolio name already exists';
            }
        }

        return $this->render('user/portfolios.html.twig', array(
            'error' => $error,
            'form' => $form->createView(),
            'portfolios' => $user->getPortfolios()
        ));
    }

    /**
     * Matches /portfolio/*
     *
     * @Route("/portfolio/{portfolio_id}", name="user_portfolio")
     */
    public function portfolio(Request $request, $portfolio_id)
    {
        $error = false;

        $portfolio = $this->getDoctrine()
            ->getRepository('AppBundle:Portfolio')
            ->find($portfolio_id);

        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // peform symbol lookup, validate symbol
            $symbol = strtolower($stock->getSymbol());
            $url = 'http://d.yimg.com/aq/autoc?query='.urlencode($symbol).'&region=US&lang=en-US';
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $output = curl_exec($ch); 
            curl_close($ch); 
            $result = json_decode($output);
            $found = false;
            if ($result && isset($result->ResultSet) && isset($result->ResultSet->Result)) 
            {
                foreach ($result->ResultSet->Result as $record) 
                {
                    if (isset($record->symbol) && strtolower($record->symbol) == $symbol) 
                    {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) 
            {
                $stock->setPortfolio($portfolio);

                $em = $this->getDoctrine()->getManager();
                $em->persist($stock);
                try
                {
                    $em->flush();
                } catch (\Doctrine\DBAL\DBALException $e)
                {
                    // we can put out a duplicate stock error message here, but for now let's pretend it was added without issue for now
                }
            } else 
            {
                $error = 'Invalid stock symbol';
            }
        }

        return $this->render('user/portfolio.html.twig', array(
            'error' => $error,
            'form' => $form->createView(),
            'stocks' => $portfolio->getStocks(),
            'data' => $portfolio->getPerformanceData()
        ));
    }
}
